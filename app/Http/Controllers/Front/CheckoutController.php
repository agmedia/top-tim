<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Session\CheckoutSession;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontBaseController;
use App\Models\Back\Settings\Settings;
use App\Models\Front\AgCart;
use App\Models\Front\Checkout\Order;
use App\Models\Front\Checkout\Payment\MyPos;
use App\Models\Front\Checkout\Shipping\Gls;
use App\Models\Front\Loyalty;
use App\Models\TagManager;
use App\Services\CheckoutMethodResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use \stdClass;

class CheckoutController extends FrontBaseController
{

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function cart(Request $request)
    {
        Log::info('public function cart(Request $request)');
        Log::info($request->toArray());

        $gdl = TagManager::getGoogleCartDataLayer($this->shoppingCart()->get());

       // Log::info($this->shoppingCart()->get());

        return view('front.checkout.cart', compact('gdl'));
    }


    /**
     * @param Request $request
     * @param string  $step
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function checkout(Request $request)
    {
        $step = '';

        if ($request->has('step')) {
            $step = $request->input('step');
        }

        $is_free_shipping = (config('settings.free_shipping') < $this->shoppingCart()->get()['total']) ? true : false;

        return view('front.checkout.checkout', compact('step', 'is_free_shipping'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view(Request $request)
    {
        $data = $this->checkSession();


        $cart = $this->shoppingCart()->get();

        // HARD GUARD: košarica mora imati bar 1 stavku
        if ($this->cartIsEmpty($cart)) {
            return redirect()->route('kosarica');
        }

        if (empty($data)) {
            return redirect()->route('naplata', ['step' => 'podaci']);
        }

        try {
            $data = $this->collectData($data, config('settings.order.status.unfinished'));
        } catch (ValidationException $exception) {
            return redirect()->route('naplata', ['step' => 'dostava'])->withErrors($exception->errors());
        }

        $order = new Order();

        if (CheckoutSession::hasOrder()) {
            $data['id'] = CheckoutSession::getOrder()['id'];

            if (! $order->updateData($data)) {
                // A completed order is immutable; a new checkout gets a fresh order.
                unset($data['id']);
                $order->createFrom($data);
            }

        } else {
            $order->createFrom($data);
        }

        if ($order->isCreated()) {
            CheckoutSession::setOrder($order->getData());
        }

        if ( ! isset($data['id'])) {
            $data['id'] = CheckoutSession::getOrder()['id'];
        }

        $uvjeti = null;//DB::table('pages')->select('description')->whereIn('id', [6])->get();

        $data['payment_form'] = $order->resolvePaymentForm();

        return view('front.checkout.view', compact('data', 'uvjeti'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function order(Request $request)
    {
        // A signed WSPay callback may arrive without the customer's browser session.
        if ($request->has('ShoppingCartID')) {
            $reference = $request->input('ShoppingCartID');
            if (! is_string($reference) || ! preg_match('/^([1-9][0-9]*)-[0-9]{4}-[A-Za-z0-9]{8}$/D', $reference, $matches)) {
                abort(403);
            }
            $order = (new Order())->setData($matches[1]);
            $stored = $order->getData();
            if (! $stored || $stored->payment_code !== 'wspay' || ! $order->finish($request)) {
                return redirect()->route('checkout.error');
            }
            CheckoutSession::setOrder($stored);
            return redirect()->route('checkout.success');
        }

        abort_unless($request->isMethod('post'), 405);

        $sessionOrder = CheckoutSession::getOrder();
        if (! $sessionOrder || empty($sessionOrder['id'])) {
            abort(403);
        }

        foreach (['mypos_checkout', 'provjera', 'order_number'] as $field) {
            if ($request->has($field) && (string) $request->input($field) !== (string) $sessionOrder['id']) {
                abort(403);
            }
        }

        $order = (new Order())->setData($sessionOrder['id']);
        $stored = $order->getData();
        if (! $stored) {
            abort(404);
        }

        if ($request->has('mypos_checkout')) {
            if ($stored->payment_code !== 'mypos' || ! in_array((int) $stored->order_status_id, [
                config('settings.order.status.unfinished'), config('settings.order.status.declined'),
            ], true)) {
                abort(403);
            }
            $this->validateSelectedMethods($stored);
            (new MyPos($stored))->process();
            return redirect()->route('checkout.error');
        }

        // Online receipts are processed by their authenticated gateway handlers only.
        if (! in_array($stored->payment_code, ['bank', 'cod', 'pickup'], true)) {
            abort(403);
        }

        $this->validateSelectedMethods($stored);

        return $order->finish($request)
            ? redirect()->route('checkout.success')
            : redirect()->route('checkout.error');
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function success(Request $request)
    {
        Log::info('success(Request $request)');
        Log::info($request->toArray());

        $data['order'] = CheckoutSession::getOrder();

        Log::info('CheckoutSession::getOrder()');
        Log::info(CheckoutSession::getOrder());

        if ( ! $data['order']) {
            return redirect()->route('index');
        }

        $order_data = $this->resolveFinishedOrder($data['order']['id']);

        if (isset($order_data['order'])) {
            $data['ids'] = $order_data['ids'];
            $data['order'] = $order_data['order'];
            $data['google_tag_manager'] = $order_data['google_tag_manager'];

            return view('front.checkout.success', compact('data'));
        }

        return redirect()->route('kosarica');
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function myposSuccess(Request $request)
    {
        $order = (new MyPos())->returnedOrder($request);
        if (! $order) {
            abort(403);
        }

        CheckoutSession::setOrder($order);
        return redirect()->route('checkout.success');
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function error()
    {
        return view('front.checkout.error');
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param $id
     *
     * @return array
     */
    private function resolveFinishedOrder($id): array
    {
        $order = \App\Models\Back\Orders\Order::where('id', $id)->first();

        if (! $order) {
            return [];
        }

        // HARD GUARD: narudžba bez stavki je nevažeća
        if ($order->products()->count() === 0) {
            Log::warning('Attempt to finish order with no products', [
                'order_id' => $order->id,
                'payment_code' => $order->payment_code,
                'session_cart' => session()->get(config('session.cart')),
            ]);

            return [];
        }

        $offline = in_array($order->payment_code, ['bank', 'cod', 'pickup'], true);
        $allowedStatuses = $offline
            ? [config('settings.order.status.new'), config('settings.order.status.paid'), config('settings.order.status.send')]
            : [config('settings.order.status.paid'), config('settings.order.status.send')];

        if (! in_array((int) $order->order_status_id, $allowedStatuses, true)) {
            return [];
        }

        // Rendering a return page never changes payment status, inventory or sends mail.
        $data['order'] = $order;
        $data['ids'] = $order->products->pluck('id')->values()->all();
        $this->forgetCheckoutCache();
        $this->shoppingCart()->flush()->resolveDB();

        $data['google_tag_manager'] = TagManager::getGoogleSuccessDataLayer($order);

        return $data;
    }



    /**
     * @return array
     */
    private function checkSession(): array
    {
        if (CheckoutSession::hasAddress() && CheckoutSession::hasShipping() && CheckoutSession::hasPayment()) {
            return [
                'address'  => CheckoutSession::getAddress(),
                'shipping' => CheckoutSession::getShipping(),
                'payment'  => CheckoutSession::getPayment(),
                'comment'  => CheckoutSession::getComment()
            ];
        }

        return [];
    }


    /**
     * @param array $data
     * @param int   $order_status_id
     *
     * @return array
     */
    private function collectData(array $data, int $order_status_id): array
    {
        $cart = $this->shoppingCart()->get();
        [$shipping, $payment] = app(CheckoutMethodResolver::class)->resolve(
            (string) ($data['address']['state'] ?? ''),
            (string) $data['shipping'],
            (string) $data['payment'],
            $cart
        );

        $response                    = [];
        $response['address']         = $data['address'];
        $response['shipping']        = $shipping;
        $response['payment']         = $payment;
        $response['comment']         = isset($data['comment']) ? $data['comment'] : '';
        $response['cart']            = $cart;
        $response['order_status_id'] = $order_status_id;

        return $response;
    }

    private function validateSelectedMethods($order): void
    {
        app(CheckoutMethodResolver::class)->resolve(
            (string) $order->shipping_state,
            (string) $order->shipping_code,
            (string) $order->payment_code,
            $this->shoppingCart()->get()
        );
    }


    /**
     * @param Request $request
     *
     * @return bool
     */
    private function validateKeksResponse(Request $request): bool
    {
        if ($request->has('status') && ! $request->input('status')) {
            $token = $request->header('Authorization');

            if ($token) {
                $keks_token = Settings::get('payment', 'list.keks')->first();

                if (isset($keks_token->data->token)) {
                    return hash_equals($keks_token->data->token, str_replace('Token ', '', $token));
                }
            }
        }

        return false;
    }


    /**
     * @return AgCart
     */
    private function shoppingCart(): AgCart
    {
        if (session()->has(config('session.cart'))) {
            return new AgCart(session(config('session.cart')));
        }

        return new AgCart(config('session.cart'));
    }


    /**
     * @return void
     */
    private function forgetCheckoutCache(): void
    {
        CheckoutSession::forgetOrder();
        CheckoutSession::forgetStep();
        CheckoutSession::forgetPayment();
        CheckoutSession::forgetShipping();
        CheckoutSession::forgetComment();
    }

    private function cartIsEmpty(array $cart): bool
    {
        return (int)($cart['count'] ?? 0) < 1;
    }

}
