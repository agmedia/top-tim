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
        $data = [];
        $data['action'] = route('checkout');
        $data['order_id'] = $this->order->id;
        $data['payment_code'] = $payment_method->first()->code;

        return view('front.checkout.payment.mypos', compact('data'));
    }


    /**
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Mypos\IPC\IPC_Exception
     */
    public function process()
    {
        $payment_method = $this->getPayment();

        $countries = Storage::disk('assets')->get('country.json');
        $countries = collect(json_decode($countries, true))->where('name', $this->order->payment_state)->first();
        $country = $countries['iso_code_3'];

        $url = $payment_method->data->test ? $this->url['test'] : $this->url['live'];

        //
        $cnf = $this->setConfig($url);

        $customer = new \Mypos\IPC\Customer();
        $customer->setFirstName($this->order->payment_fname);
        $customer->setLastName($this->order->payment_lname);
        $customer->setEmail($this->order->payment_email);
        $customer->setPhone($this->order->payment_phone);
        $customer->setCountry($country);
        $customer->setAddress($this->order->payment_address);
        $customer->setCity($this->order->payment_city);
        $customer->setZip($this->order->payment_zip);

        // dd($this->order->products);
        $cart = new \Mypos\IPC\Cart;
        // dd($this->order->totals);
        foreach ($this->order->products as $product) {
            $cart->add($product->name, $product->quantity, number_format($product->price, 2, '.', '')); //name, quantity, price
        }
        foreach ($this->order->totals as $add) {
            if ($add->code == 'shipping') {
                $cart->add($add->title, 1, number_format($add->value, 2, '.', ''));
            }
        }

        $purchase = new \Mypos\IPC\Purchase($cnf);
        $purchase->setUrlCancel(url($payment_method->data->mypos_set_url_cancel)); //User comes here after purchase cancelation
        $purchase->setUrlOk(url($payment_method->data->mypos_set_url_ok)); //User comes here after purchase success
        $purchase->setUrlNotify(url($payment_method->data->mypos_set_url_notify)); //IPC sends POST reuquest to this address with purchase status
        $purchase->setOrderID(Str::random(4) . $this->order->id); //Some unique ID
        $purchase->setCurrency('EUR');
        // $purchase->setNote('Some note'); //Not required
        $purchase->setCustomer($customer);
        $purchase->setCart($cart);

        $purchase->setCardTokenRequest(\Mypos\IPC\Purchase::CARD_TOKEN_REQUEST_PAY_AND_STORE);
        $purchase->setPaymentParametersRequired(\Mypos\IPC\Purchase::PURCHASE_TYPE_FULL);
        $purchase->setPaymentMethod(\Mypos\IPC\Purchase::PAYMENT_METHOD_BOTH);

        try {
            $purchase->process();
        } catch (\Mypos\IPC\IPC_Exception $e) {
            Log::info('process exception');
            Log::info($e->getMessage());
            //Invalid params. To see details use "echo $ex->getMessage();"
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
        $pass = false;
        $status = config('settings.order.status.declined');

        if ($request->has('IPCmethod') && $request->input('IPCmethod') == 'IPCPurchaseOK') {
            $pass = true;
            $status = config('settings.order.status.paid');
        }

        $order->update([
            'order_status_id' => $status
        ]);

        if ($pass) {
            Transaction::insert([
                'order_id' => $order->id,
                'success' => 1,
                'amount' => $request->input('Amount'),
                'signature' => $request->input('Signature'),
                'payment_type' => $request->input('CardType'),
                'payment_plan' => $request->input('PAN'),
                'payment_partner' => $request->input('CustomerEmail'),
                'datetime' => $request->input('RequestDateTime'),
                'approval_code' => $request->input('SID'),
                'pg_order_id' => substr($request->input('OrderID'), 4),
                'lang' => $request->input('Currency'),
                'stan' => $request->input('RequestSTAN'),
                'error' => $request->input('CardToken'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return true;
        }

        Transaction::insert([
            'order_id' => $order->id,
            'success' => 0,
            'amount' => $request->input('Amount'),
            'signature' => $request->input('Signature'),
            'payment_type' => $request->input('CardType'),
            'payment_plan' => $request->input('PAN'),
            'payment_partner' => $request->input('CustomerEmail'),
            'datetime' => $request->input('RequestDateTime'),
            'approval_code' => $request->input('SID'),
            'pg_order_id' => substr($request->input('OrderID'), 4),
            'lang' => $request->input('Currency'),
            'stan' => $request->input('RequestSTAN'),
            'error' => $request->input('CardToken'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
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
        $payment_method = $this->getPayment();
        $url = $payment_method->data->test ? $this->url['test'] : $this->url['live'];

        $cnf = $this->setConfig($url);

        try {
            $response = \Mypos\IPC\Response::getInstance($cnf, $request->toArray(), \Mypos\IPC\Defines::COMMUNICATION_FORMAT_POST);
            $data = $response->getData(CASE_LOWER);

            if ($data['ipcmethod'] == 'IPCPurchaseNotify') {
                return response('OK', 200);

            } else if ($data['ipcmethod'] == 'IPCPurchaseCancel') {
                return redirect(route('checkout.error'));

            } else if ($data['ipcmethod'] == 'IPCPurchaseOK') {
                $order = Order::query()->find(substr($request->input('OrderID'), 4));
                $this->finishOrder($order, $request);

                return redirect(route('checkout.success'));
            }

        } catch (\Mypos\IPC\IPC_Exception $e) {
            Log::info('notify exception');
            Log::info($e->getMessage());
            //Display Some general error or redirect to merchant store home page
        }
    }


    /*******************************************************************************
     *                                Copyright : AGmedia                           *
     *                              email: filip@agmedia.hr                         *
     *******************************************************************************/

    private function setConfig(string $url)
    {
        $cnf = new \Mypos\IPC\Config();
        $cnf->setIpcURL($url);
        $cnf->setLang('en');
        $cnf->setVersion('1.4');
        $configurationPackage = 'eyJzaWQiOiIwMDAwMDAwMDAwMDAwMTAiLCJjbiI6IjYxOTM4MTY2NjEwIiwicGsiOiItLS0tLUJFR0lOIFJTQSBQUklWQVRFIEtFWS0tLS0tXHJcbk1JSUNYQUlCQUFLQmdRQ2YwVGRjVHVwaGI3WCtad2VrdDFYS0VXWkRjelNHZWNmbzZ2UWZxdnJhZjVWUHpjbkpcclxuMk1jNUo3MkhCbTB1OThFSkhhbitubGUyV09aTVZHSXRUYVwvMmsxRlJXd2J0N2lRNWR6RGg1UEVlWkFTZzJVV2VcclxuaG9SOEw4TXBOQnFINmg3WklUd1ZUZlJTNExzQnZsRWZUN1B6aG01WUpLZk0rQ2R6RE0rTDlXVkVHd0lEQVFBQlxyXG5Bb0dBWWZLeHdVdEVicTh1bFZyRDNubldoRitoazFrNktlamRVcTBkTFlOMjl3OFdqYkNNS2I5SWFva21xV2lRXHJcbjVpWkdFcll4aDdHNEJEUDhBV1wvK005SFhNNG9xbTVTRWtheGhiVGxna3MrRTFzOWRUcGRGUXZMNzZUdm9kcVN5XHJcbmwyRTJCZ2hWZ0xMZ2tkaFJuOWJ1YUZ6WXRhOTVKS2ZneUtHb25OeHNRQTM5UHdFQ1FRREtiRzBLcDZLRWtOZ0Jcclxuc3JDcTNDeDJvZDVPZmlQREc4ZzNSWVpLeFwvTzlkTXk1Q00xNjBEd3VzVkpwdXl3YnBSaGNXcjNna3owUWdSTWRcclxuSVJWd3l4TmJBa0VBeWgzc2lwbWNnTjdTRDh4QkdcL010QllQcVdQMXZ4aFNWWVBmSnp1UFUzZ1M1TVJKelFIQnpcclxuc1ZDTGhUQlk3aEhTb3FpcWxxV1lhc2k4MUp6QkV3RXVRUUpCQUt3OXFHY1pqeU1IOEpVNVREU0dsbHIzanlieFxyXG5GRk1QajhUZ0pzMzQ2QUI4b3pxTExcL1RodldQcHhIdHRKYkg4UUFkTnV5V2RnNmRJZlZBYTk1aDdZK01DUUVaZ1xyXG5qUkRsMUJ6N2VXR08yYzBGcTlPVHozSVZMV3BubUd3ZlcrSHlheGl6eEZoVitGT2oxR1VWaXI5aHlsVjdWMERVXHJcblFqSWFqeXZcL29lRFdoRlE5d1FFQ1FDeWRoSjZOYU5RT0NaaCs2UVRySDNUQzVNZUJBMVllaXBvZTcrQmhzTE5yXHJcbmNGRzhzOXNUeFJubHRjWmwxZFhhQlNlbXZwTnZCaXpuMEt6aThHM1pBZ2M9XHJcbi0tLS0tRU5EIFJTQSBQUklWQVRFIEtFWS0tLS0tIiwicGMiOiItLS0tLUJFR0lOIENFUlRJRklDQVRFLS0tLS1cclxuTUlJQnNUQ0NBUm9DQ1FDQ1BqTnR0R05RV0RBTkJna3Foa2lHOXcwQkFRc0ZBREFkTVFzd0NRWURWUVFHRXdKQ1xyXG5SekVPTUF3R0ExVUVDZ3dGYlhsUVQxTXdIaGNOTVRneE1ERXlNRGN3T1RFeldoY05Namd4TURBNU1EY3dPVEV6XHJcbldqQWRNUXN3Q1FZRFZRUUdFd0pDUnpFT01Bd0dBMVVFQ2d3RmJYbFFUMU13Z1o4d0RRWUpLb1pJaHZjTkFRRUJcclxuQlFBRGdZMEFNSUdKQW9HQkFNTCtWVG1pWTR5Q2hvT1RNWlRYQUlHXC9tayt4ZlwvOW1qd0h4V3p4dEJKYk5uY05LXHJcbjBPTEkwVlhZS1cyR2dWa2xHSEhRanZldzFoVEZrRUdqbkNKN2Y1Q0RuYmd4ZXZ0eUFTREdzdDkyYTZ4Y0FlZEVcclxuYWRQMG5GWGhVeitjWVlJZ0ljZ2ZEY1gzWldlTkVGNWtzY3F5NTJrcEQyTzduRk5DVis4NXZTNGR1SkJOQWdNQlxyXG5BQUV3RFFZSktvWklodmNOQVFFTEJRQURnWUVBQ2oweGIrdE5ZRVJKa0wrcCt6RGNCc0JLNFJ2a25QbHBrK1lQXHJcbmVwaHVuRzJkQkdPbWdcL1dLZ29EMVBMV0QyYkVmR2dKeFlCSWc5cjF3TFlwREMxdHhoeFYrMk9CUVM4NktVTGgwXHJcbk5FY3IwcUVZMDVtSTRGbEUrRFwvQnBUXC8rV0Z5S2tadWc5MnJLMEZsejcxWHlcLzltQlhiUWZtK1lLNmw5cm9SWWRcclxuSjRzSGVRYz1cclxuLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLSIsImlkeCI6MX0=';

        $cnf->loadConfigurationPackage($configurationPackage);

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

}
