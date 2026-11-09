<?php

namespace App\Models\Front\Checkout\Payment;

use App\Models\Back\Orders\Order;
use App\Models\Front\Checkout\PaymentMethod;
use App\Services\CheckoutCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mypos\IPC\Cart;
use Mypos\IPC\Config;
use Mypos\IPC\Customer;
use Mypos\IPC\IPC_Exception;
use Mypos\IPC\Purchase;
use Mypos\IPC\Response;

/**
 * Class MyPos
 * @package App\Models\Front\Checkout\Payment
 */
class MyPos
{

    /**
     * @var Order
     */
    private $order;

    /**
     * @var string[]
     */
    private $url = [
        'test' => 'https://mypos.com/vmp/checkout-test',
        'live' => 'https://mypos.com/vmp/checkout'
    ];


    /**
     * MyPos constructor.
     *
     * @param $order
     */
    public function __construct($order = null)
    {
        $this->order = $order;
    }


    /**
     * @param Collection|null $payment_method
     * @param array|null      $options
     * @param Request|null    $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function resolveFormView(?Collection $payment_method = null, ?array $options = null, ?Request $request = null)
    {
        $data                 = [];
        $data['action']       = route('checkout');
        $data['order_id']     = $this->order->id;
        $data['payment_code'] = $payment_method->first()->code;

        return view('front.checkout.payment.mypos', compact('data'));
    }


    /**
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws IPC_Exception
     */
    public function process()
    {
        $payment_method = $this->getPayment();
        $country        = $this->resolveCountry();
        //
        $cnf = $this->setConfig($payment_method);

        $customer = new Customer();
        $customer->setFirstName($this->order->payment_fname);
        $customer->setLastName($this->order->payment_lname);
        $customer->setEmail($this->order->payment_email);
       // $customer->setPhone($this->order->payment_phone);
        $customer->setCountry($country);
        $customer->setAddress($this->order->payment_address);
        $customer->setCity($this->order->payment_city);
        $customer->setZip($this->order->payment_zip);

        // dd($this->order->products);
        $cart = new Cart;
        $products_total = 0;
        $order_total = 0;
        // dd($this->order->totals);
        foreach ($this->order->products as $product) {
            $price = number_format($product->price, 2, '.', '');
            $quantity = $product->quantity ? intval($product->quantity) : 1;
            $products_total += $quantity * $price;

            $cart->add($product->name, $quantity, $price); //name, quantity, price
        }

        foreach ($this->order->totals as $add) {
            if ($add->code == 'shipping') {
                $shipping = number_format($add->value, 2, '.', '');
                $products_total = $products_total + $shipping;

                $cart->add($add->title, 1, $shipping);
            }

            if ($add->code == 'total') {
                $order_total = number_format($add->value, 2, '.', '');
            }
        }

        $adjustment = round((float) $this->order->total - $cart->getTotal(), 2);
        if ($adjustment != 0) {
            $cart->add($adjustment < 0 ? 'Popust' : 'Naknada', 1, number_format($adjustment, 2, '.', ''));
        }

        $purchase = new Purchase($cnf);
        $purchase->setUrlCancel(route('checkout.mypos.cancel')); //User comes here after purchase cancelation
        //$purchase->setUrlCancel(url($payment_method->data->mypos_set_url_cancel)); //User comes here after purchase cancelation
        $purchase->setUrlOk(route('checkout.mypos.success')); //User comes here after purchase success
        //$purchase->setUrlOk(url($payment_method->data->mypos_set_url_ok)); //User comes here after purchase success
        $purchase->setUrlNotify(url($payment_method->data->mypos_set_url_notify)); //IPC sends POST reuquest to this address with purchase status
        $purchase->setOrderID(Str::random(4) . $this->order->id); //Some unique ID
        $purchase->setCurrency('EUR');
        // $purchase->setNote('Some note'); //Not required
        $purchase->setCustomer($customer);
        $purchase->setCart($cart);

        $purchase->setCardTokenRequest(Purchase::CARD_TOKEN_REQUEST_NONE);
        $purchase->setPaymentParametersRequired(Purchase::PURCHASE_TYPE_SIMPLIFIED_PAYMENT_PAGE);
        $purchase->setPaymentMethod(Purchase::PAYMENT_METHOD_BOTH);

        try {
            $purchase->process();
        } catch (IPC_Exception $e) {
            Log::info('Process MyPos order exception');
            Log::info($e->getMessage());
        }
    }


    /**
     * @param Order $order
     * @param null  $request
     *
     * @return bool
     */
    public function finishOrder(Order $order, Request $request): bool
    {
        $data = $this->verifiedData($request);

        // Only the server notification settles payments; browser returns only display them.
        if (! $data || strtoupper((string) ($data['ipcmethod'] ?? '')) !== 'IPCPURCHASENOTIFY'
            || ! $this->matchesOrder($data, $order)) {
            return false;
        }

        return app(CheckoutCompletion::class)->paid($order->id, 'mypos', [
            'amount' => $data['amount'],
            // Keep an audit fingerprint within the existing VARCHAR(255) column.
            'signature' => hash('sha256', (string) $request->input('Signature', $request->input('signature', ''))),
            'payment_type' => $data['cardtype'] ?? null,
            'payment_plan' => null,
            'payment_partner' => $data['customeremail'] ?? null,
            'datetime' => $data['requestdatetime'] ?? now(),
            'approval_code' => $data['ipc_trnref'],
            'pg_order_id' => $data['orderid'],
            'lang' => $data['currency'],
            'stan' => $data['requeststan'] ?? null,
            'error' => null,
        ]);
    }

    public function notify(Request $request)
    {
        $data = $this->verifiedData($request);

        if (! $data) {
            return response('Invalid payment notification', 400);
        }

        $method = strtoupper((string) ($data['ipcmethod'] ?? ''));
        if ($method === 'IPCPURCHASEROLLBACK') {
            $id = $this->orderId($data['orderid'] ?? null);
            $order = $id ? Order::find($id) : null;
            if (! $order || ! $this->matchesOrder($data, $order, false)) {
                return response('Payment does not match order', 400);
            }
            return app(CheckoutCompletion::class)->rollback($id, $data['orderid'], $data['amount'])
                ? response('OK', 200) : response('Payment could not be reversed', 409);
        }
        if ($method !== 'IPCPURCHASENOTIFY') {
            // A browser success, cancellation or pending response cannot authorize payment.
            return response('OK', 200);
        }

        $id = $this->orderId($data['orderid'] ?? null);
        $order = $id ? Order::find($id) : null;

        if (! $order || ! $this->matchesOrder($data, $order)) {
            return response('Payment does not match order', 400);
        }

        // Recheck the signature at the shared settlement entry point as well.
        return $this->finishOrder($order, $request)
            ? response('OK', 200)
            : response('Payment could not be completed', 409);
    }

    /** Authenticate a browser return without mutating the order. */
    public function returnedOrder(Request $request): ?Order
    {
        $data = $this->verifiedData($request);

        if (! $data || strtoupper((string) ($data['ipcmethod'] ?? '')) !== 'IPCPURCHASEOK') {
            return null;
        }

        $id = $this->orderId($data['orderid'] ?? null);
        $order = $id ? Order::find($id) : null;

        return $order && $this->matchesOrder($data, $order) ? $order : null;
    }

    private function verifiedData(Request $request): ?array
    {
        try {
            $config = $this->setConfig();
            $response = Response::getInstance($config, $request->all(), \Mypos\IPC\Defines::COMMUNICATION_FORMAT_POST);
            $data = $response->getData(CASE_LOWER);

            $rollback = strtoupper((string) ($data['ipcmethod'] ?? '')) === 'IPCPURCHASEROLLBACK';
            if ((! isset($data['sid']) && ! $rollback) || (isset($data['sid'])
                && (! is_scalar($data['sid']) || ! hash_equals((string) $config->getSid(), (string) $data['sid'])))) {
                return null;
            }

            // Some responses contain a status; an explicit failure is never a payment.
            if (isset($data['status']) && (string) $data['status'] !== '0') {
                return null;
            }

            return $data;
        } catch (\Throwable $exception) {
            Log::warning('Rejected unauthenticated myPOS response', ['reason' => $exception->getMessage()]);
            return null;
        }
    }

    private function matchesOrder(array $data, Order $order, bool $requireTransaction = true): bool
    {
        return $order->payment_code === 'mypos'
            && $this->orderId($data['orderid'] ?? null) === (int) $order->id
            && (! $requireTransaction || (isset($data['ipc_trnref']) && is_scalar($data['ipc_trnref']) && (string) $data['ipc_trnref'] !== ''))
            && ($data['currency'] ?? null) === 'EUR'
            && app(CheckoutCompletion::class)->sameAmount($data['amount'] ?? null, $order->total);
    }

    private function orderId($reference): ?int
    {
        return is_string($reference) && preg_match('/^[A-Za-z0-9]{4}([1-9][0-9]*)$/D', $reference, $matches)
            ? (int) $matches[1] : null;
    }


    public function cancel(Request $request)
    {
        Log::info('cancel');
        Log::info($request->toArray());

        return redirect()->route('kosarica');
    }

    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    /**
     * @param $payment_method
     *
     * @return Config
     * @throws IPC_Exception
     */
    private function setConfig($payment_method = null)
    {
        if (is_null($payment_method)) {
            $payment_method = $this->getPayment();
        }

        $url                   = $payment_method->data->test ? $this->url['test'] : $this->url['live'];
        $configuration_package = $payment_method->data->test ?
            $payment_method->data->mypos_virtual_configuration_package_test :
            $payment_method->data->mypos_virtual_configuration_package_live;

        $cnf = new Config();
        $cnf->setIpcURL($url);
        $cnf->setLang(current_locale());
        $cnf->setVersion('1.4');

        $cnf->loadConfigurationPackage($configuration_package);

        return $cnf;
    }


    /**
     * @return mixed
     */
    private function getPayment()
    {
        $payment = new PaymentMethod('mypos');
        $payment = $payment->getMethod();

        return $payment->first();
    }


    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function resolveCountry(): string
    {
        $countries = Storage::disk('assets')->get('country.json');
        $name      = 'Croatia';

        if ($this->order) {
            $name = $this->order->payment_state;
        }

        $countries = collect(json_decode($countries, true))->where('name', $name)->first();

        return $countries['iso_code_3'];
    }

}
