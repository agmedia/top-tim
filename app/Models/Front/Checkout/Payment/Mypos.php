<?php

namespace App\Models\Front\Checkout\Payment;
use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


/**
 * Class Corvus
 * @package App\Models\Front\Checkout\Payment
 */
class Mypos
{

    /**
     * @var Order
     */
    private $order;


    /**
     * Corvus constructor.
     *
     * @param $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }


    /**
     * @param Collection|null $payment_method
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function resolveFormView(Collection $payment_method = null, array $options = null, Request $request = null)
    {


//dd($request);

        if (isset($_GET['terms'])){


            if (!$payment_method) {
                return '';
            }

            $payment_method = $payment_method->first();

            $cnf = new \Mypos\IPC\Config();
            $cnf->setIpcURL('https://mypos.com/vmp/checkout-test/');
            $cnf->setLang('hr');
            $cnf->setVersion('1.4');
            $configurationPackage = 'eyJzaWQiOiIwMDAwMDAwMDAwMDAwMTAiLCJjbiI6IjYxOTM4MTY2NjEwIiwicGsiOiItLS0tLUJFR0lOIFJTQSBQUklWQVRFIEtFWS0tLS0tXHJcbk1JSUNYQUlCQUFLQmdRQ2YwVGRjVHVwaGI3WCtad2VrdDFYS0VXWkRjelNHZWNmbzZ2UWZxdnJhZjVWUHpjbkpcclxuMk1jNUo3MkhCbTB1OThFSkhhbitubGUyV09aTVZHSXRUYVwvMmsxRlJXd2J0N2lRNWR6RGg1UEVlWkFTZzJVV2VcclxuaG9SOEw4TXBOQnFINmg3WklUd1ZUZlJTNExzQnZsRWZUN1B6aG01WUpLZk0rQ2R6RE0rTDlXVkVHd0lEQVFBQlxyXG5Bb0dBWWZLeHdVdEVicTh1bFZyRDNubldoRitoazFrNktlamRVcTBkTFlOMjl3OFdqYkNNS2I5SWFva21xV2lRXHJcbjVpWkdFcll4aDdHNEJEUDhBV1wvK005SFhNNG9xbTVTRWtheGhiVGxna3MrRTFzOWRUcGRGUXZMNzZUdm9kcVN5XHJcbmwyRTJCZ2hWZ0xMZ2tkaFJuOWJ1YUZ6WXRhOTVKS2ZneUtHb25OeHNRQTM5UHdFQ1FRREtiRzBLcDZLRWtOZ0Jcclxuc3JDcTNDeDJvZDVPZmlQREc4ZzNSWVpLeFwvTzlkTXk1Q00xNjBEd3VzVkpwdXl3YnBSaGNXcjNna3owUWdSTWRcclxuSVJWd3l4TmJBa0VBeWgzc2lwbWNnTjdTRDh4QkdcL010QllQcVdQMXZ4aFNWWVBmSnp1UFUzZ1M1TVJKelFIQnpcclxuc1ZDTGhUQlk3aEhTb3FpcWxxV1lhc2k4MUp6QkV3RXVRUUpCQUt3OXFHY1pqeU1IOEpVNVREU0dsbHIzanlieFxyXG5GRk1QajhUZ0pzMzQ2QUI4b3pxTExcL1RodldQcHhIdHRKYkg4UUFkTnV5V2RnNmRJZlZBYTk1aDdZK01DUUVaZ1xyXG5qUkRsMUJ6N2VXR08yYzBGcTlPVHozSVZMV3BubUd3ZlcrSHlheGl6eEZoVitGT2oxR1VWaXI5aHlsVjdWMERVXHJcblFqSWFqeXZcL29lRFdoRlE5d1FFQ1FDeWRoSjZOYU5RT0NaaCs2UVRySDNUQzVNZUJBMVllaXBvZTcrQmhzTE5yXHJcbmNGRzhzOXNUeFJubHRjWmwxZFhhQlNlbXZwTnZCaXpuMEt6aThHM1pBZ2M9XHJcbi0tLS0tRU5EIFJTQSBQUklWQVRFIEtFWS0tLS0tIiwicGMiOiItLS0tLUJFR0lOIENFUlRJRklDQVRFLS0tLS1cclxuTUlJQnNUQ0NBUm9DQ1FDQ1BqTnR0R05RV0RBTkJna3Foa2lHOXcwQkFRc0ZBREFkTVFzd0NRWURWUVFHRXdKQ1xyXG5SekVPTUF3R0ExVUVDZ3dGYlhsUVQxTXdIaGNOTVRneE1ERXlNRGN3T1RFeldoY05Namd4TURBNU1EY3dPVEV6XHJcbldqQWRNUXN3Q1FZRFZRUUdFd0pDUnpFT01Bd0dBMVVFQ2d3RmJYbFFUMU13Z1o4d0RRWUpLb1pJaHZjTkFRRUJcclxuQlFBRGdZMEFNSUdKQW9HQkFNTCtWVG1pWTR5Q2hvT1RNWlRYQUlHXC9tayt4ZlwvOW1qd0h4V3p4dEJKYk5uY05LXHJcbjBPTEkwVlhZS1cyR2dWa2xHSEhRanZldzFoVEZrRUdqbkNKN2Y1Q0RuYmd4ZXZ0eUFTREdzdDkyYTZ4Y0FlZEVcclxuYWRQMG5GWGhVeitjWVlJZ0ljZ2ZEY1gzWldlTkVGNWtzY3F5NTJrcEQyTzduRk5DVis4NXZTNGR1SkJOQWdNQlxyXG5BQUV3RFFZSktvWklodmNOQVFFTEJRQURnWUVBQ2oweGIrdE5ZRVJKa0wrcCt6RGNCc0JLNFJ2a25QbHBrK1lQXHJcbmVwaHVuRzJkQkdPbWdcL1dLZ29EMVBMV0QyYkVmR2dKeFlCSWc5cjF3TFlwREMxdHhoeFYrMk9CUVM4NktVTGgwXHJcbk5FY3IwcUVZMDVtSTRGbEUrRFwvQnBUXC8rV0Z5S2tadWc5MnJLMEZsejcxWHlcLzltQlhiUWZtK1lLNmw5cm9SWWRcclxuSjRzSGVRYz1cclxuLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLSIsImlkeCI6MX0=';

            $cnf->loadConfigurationPackage($configurationPackage);

            $countries = Storage::disk('assets')->get('country.json');
            $countries = collect(json_decode($countries, true))->where('name', $this->order->payment_state)->first();
            $country = $countries['iso_code_3'];

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
            $purchase->setUrlCancel($payment_method->data->callback); //User comes here after purchase cancelation
            $purchase->setUrlOk($payment_method->data->callback); //User comes here after purchase success
            $purchase->setUrlNotify($payment_method->data->callback); //IPC sends POST reuquest to this address with purchase status
            $purchase->setOrderID(uniqid()); //Some unique ID
            $purchase->setCurrency('EUR');
            // $purchase->setNote('Some note'); //Not required
            $purchase->setCustomer($customer);
            $purchase->setCart($cart);

            $purchase->setCardTokenRequest(\Mypos\IPC\Purchase::CARD_TOKEN_REQUEST_PAY_AND_STORE);
            $purchase->setPaymentParametersRequired(\Mypos\IPC\Purchase::PURCHASE_TYPE_FULL);
            $purchase->setPaymentMethod(\Mypos\IPC\Purchase::PAYMENT_METHOD_BOTH);

           try {
                $purchase->process();
            } catch (\Mypos\IPC\IPC_Exception $ex) {
                Log::info($ex->getMessage());
                //Invalid params. To see details use "echo $ex->getMessage();"
            }

        } else{
            return view('front.checkout.payment.mypos');

    }

  /*  if ( ! $payment_method) {
        return '';
    }

    $payment_method = $payment_method->first();

    $action = $this->url['live'];

    if ($payment_method->data->test) {
        $action = $this->url['test'];
    }

    $total = number_format($this->order->total, 2, '.', '');

    $data['currency']  = isset($options['currency']) ? $options['currency'] : 'EUR';
    $data['action']    = $action;
    $data['merchant']  = $payment_method->data->shop_id;
    $data['order_id']  = isset($options['order_number']) ? $options['order_number'] : $this->order->id;
    $data['total']     = isset($options['total']) ? $options['total'] : $total;
    $data['firstname'] = $this->order->payment_fname;
    $data['lastname']  = $this->order->payment_lname;
    $data['address']   = '';
    $data['city']      = '';
    $data['country']   = '';
    $data['postcode']  = '';
    $data['telephone'] = $this->order->payment_phone;
    $data['email']     = $this->order->payment_email;
    $data['lang']      = 'hr';
    $data['plan']      = isset($options['plan']) ? $options['plan'] : '01';
    $data['cc_name']   = isset($options['cc_name']) ? $options['cc_name'] : 'VISA';//...??
    $data['rate']      = isset($options['rate']) ? $options['rate'] : 1;
    $data['return']    = isset($options['return_url']) ? $options['return_url'] : $payment_method->data->callback;
    $data['cancel']    = route('index');
    $data['method']    = 'POST';

    $data['number_of_installments'] = 'Y0299';

    $string = 'amount' . $total . 'cardholder_email' . $data['email'] . 'cardholder_name' . $data['firstname'] . 'cardholder_phone' . $data['telephone'] . 'cardholder_surname' . $data['lastname'] . 'cartWeb shop kupnja ' . $data['order_id'] . 'currency' . $data['currency'] . 'language' . $data['lang'] . 'order_number' . $data['order_id'] . 'payment_all' . $data['number_of_installments'] . 'require_completefalsestore_id' . $data['merchant'] . 'version1.3';

    $keym = $payment_method->data->secret_key;
    $hash = hash_hmac('sha256', $string, $keym);

    $data['md5'] = $hash;

    return view('front.checkout.payment.corvus', compact('data'));*/


    }


    /**
     * @param Order $order
     * @param null  $request
     *
     * @return bool
     */
    public function finishOrder(Order $order, Request $request): bool
    {

        $status = ($request->has('approval_code') && $request->input('approval_code')!= null) ? config('settings.order.status.paid') : config('settings.order.status.declined');


        $order->update([
            'order_status_id' => $status
        ]);

        if ($request->has('approval_code')) {
            Transaction::insert([
                'order_id'        => $order->id,
                'success'         => 1,
              /*  'amount'          => $request->input('Amount'),
                'signature'       => $request->input('Signature'),
                'payment_type'    => $request->input('PaymentType'),
                'payment_plan'    => $request->input('PaymentPlan'),
                'payment_partner' => $request->input('Partner'),
                'datetime'        => $request->input('DateTime'),
                'approval_code'   => $request->input('ApprovalCode'),
                'pg_order_id'     => $request->input('CorvusOrderId'),
                'lang'            => $request->input('Lang'),
                'stan'            => $request->input('STAN'),
                'error'           => $request->input('ErrorMessage'),*/
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now()
            ]);

            return true;
        }

        Transaction::insert([
            'order_id'        => $order->id,
            'success'         => 0,
          /*  'amount'          => $request->input('Amount'),
            'signature'       => $request->input('Signature'),
            'payment_type'    => $request->input('PaymentType'),
            'payment_plan'    => $request->input('PaymentPlan'),
            'payment_partner' => null,
            'datetime'        => $request->input('DateTime'),
            'approval_code'   => $request->input('ApprovalCode'),
            'pg_order_id'     => null,
            'lang'            => $request->input('Lang'),
            'stan'            => null,
            'error'           => $request->input('ErrorMessage'),*/
            'created_at'      => Carbon::now(),
            'updated_at'      => Carbon::now()
        ]);

        return false;
    }

}
