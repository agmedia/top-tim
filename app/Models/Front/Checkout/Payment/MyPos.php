<?php

namespace App\Models\Front\Checkout\Payment;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\Transaction;
use App\Models\Front\Checkout\PaymentMethod;
use Carbon\Carbon;
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
    public function resolveFormView(Collection $payment_method = null, array $options = null, Request $request = null)
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
        // dd($this->order->totals);
        foreach ($this->order->products as $product) {
            $cart->add($product->name, $product->quantity, number_format($product->price, 2, '.', '')); //name, quantity, price
        }
        foreach ($this->order->totals as $add) {
            if ($add->code == 'shipping') {
                $cart->add($add->title, 1, number_format($add->value, 2, '.', ''));
            }
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
        Log::info('public function finishOrder(Order $order, Request $request): bool');
        Log::info($request->toArray());

        $pass   = false;
        $status = config('settings.order.status.declined');

        if ($request->has('IPCmethod') && $request->input('IPCmethod') == 'IPCPurchaseOK') {
            $pass   = true;
            $status = config('settings.order.status.paid');
        }

        $order->update([
            'order_status_id' => $status
        ]);

        if ($pass) {
            Transaction::insert([
                'order_id'        => $order->id,
                'success'         => 1,
                'amount'          => $request->input('Amount'),
                'signature'       => $request->input('Signature'),
                'payment_type'    => $request->input('CardType'),
                'payment_plan'    => $request->input('PAN'),
                'payment_partner' => $request->input('CustomerEmail'),
                'datetime'        => $request->input('RequestDateTime'),
                'approval_code'   => $request->input('SID'),
                'pg_order_id'     => substr($request->input('OrderID'), 4),
                'lang'            => $request->input('Currency'),
                'stan'            => $request->input('RequestSTAN'),
                'error'           => $request->input('CardToken'),
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now()
            ]);

            return true;
        }

        Transaction::insert([
            'order_id'        => $order->id,
            'success'         => 0,
            'amount'          => $request->input('Amount'),
            'signature'       => $request->input('Signature'),
            'payment_type'    => $request->input('CardType'),
            'payment_plan'    => $request->input('PAN'),
            'payment_partner' => $request->input('CustomerEmail'),
            'datetime'        => $request->input('RequestDateTime'),
            'approval_code'   => $request->input('SID'),
            'pg_order_id'     => substr($request->input('OrderID'), 4),
            'lang'            => $request->input('Currency'),
            'stan'            => $request->input('RequestSTAN'),
            'error'           => $request->input('CardToken'),
            'created_at'      => Carbon::now(),
            'updated_at'      => Carbon::now()
        ]);

        return false;
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector|void
     */
    public function notify(Request $request)
    {
        $cnf = $this->setConfig();

        Log::info('public function notify(Request $request)');
        Log::info($request->toArray());

        try {
            $response = Response::getInstance($cnf, $request->toArray(), \Mypos\IPC\Defines::COMMUNICATION_FORMAT_POST);
            $data     = $response->getData(CASE_LOWER);

        } catch (IPC_Exception $e) {
            Log::info('Notify MyPos order exception');
            Log::info($e->getMessage());
        }

        Log::info($response->getData(CASE_LOWER));

        if ($data['ipcmethod'] == 'IPCPurchaseNotify') {
            return response('OK', 200);

        } else if ($data['ipcmethod'] == 'IPCPurchaseRollback') {
            return response('OK', 200);

        } else if ($data['ipcmethod'] == 'IPCPurchaseCancel') {
            /*return response('OK', 200);
            return redirect()->route('kosarica');*/

        } else if ($data['ipcmethod'] == 'IPCPurchaseOK') {
            $order = Order::query()->find(substr($request->input('OrderID'), 4));
            $this->finishOrder($order, $request);

            //return redirect()->route('checkout.mypos.success', ['identifier' => $request->input('OrderID')]);

        } else {
            return redirect()->route('kosarica');
        }
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
