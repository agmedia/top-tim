<?php

namespace App\Models\Front\Checkout\Payment;

use App\Models\Back\Orders\Order;
use App\Models\Back\Orders\Transaction;
use App\Services\CheckoutCompletion;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class Payway
 * @package App\Models\Front\Checkout\Payment
 */
class WSpay
{

    /**
     * @var Order
     */
    private $order;

    /**
     * @var string[]
     */
    private $url = [
        'test' => 'https://formtest.wspay.biz/Authorization.aspx',
        'live' => 'https://form.wspay.biz/Authorization.aspx'
    ];


    /**
     * Payway constructor.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }


    /**
     * @param Collection|null $payment_method
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function resolveFormView(?Collection $payment_method = null)
    {
        if ( ! $payment_method) {
            return '';
        }

        $payment_method = $payment_method->first();

        $action = $this->url['live'];

        if ($payment_method->data->test) {
            $action = $this->url['test'];
        }

        $total = number_format($this->order->total, 2, ',', '');
        $reference = $this->order->id . '-' . date('Y') . '-' . Str::random(8);
        $hash = hash('sha512', $payment_method->data->shop_id . $payment_method->data->secret_key
            . $reference . $payment_method->data->secret_key
            . str_replace(',', '', $total) . $payment_method->data->secret_key);

        // WSPay does not sign Amount in its return. Retain the amount we signed outbound.
        Transaction::insert([
            'order_id' => $this->order->id, 'success' => 0, 'amount' => $this->order->total,
            'signature' => $hash, 'pg_order_id' => $reference, 'datetime' => now(), 'lang' => 'EUR',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $data['action'] = $action;
        $data['shop_id'] = $payment_method->data->shop_id;
        $data['order_id'] = $reference;
        $data['total'] = $total;
        $data['md5'] = $hash;
        $data['firstname'] = $this->order->payment_fname;
        $data['lastname'] = $this->order->payment_lname;
        $data['address'] = $this->order->payment_address;
        $data['city'] = $this->order->payment_city;
        $data['country'] = $this->order->payment_state;
        $data['postcode'] = $this->order->payment_zip;
        $data['phone'] = $this->order->payment_phone;
        $data['email'] = $this->order->payment_email;
        $data['lang'] = 'HR';
        $data['plan'] = '';
        $data['cc_name'] = '';//...??
        $data['currency'] = 'EUR';
        $data['rate'] = 1;
        $data['return'] = $payment_method->data->callback;
        $data['cancel'] = route('kosarica');
        $data['method'] = 'POST';

        return view('front.checkout.payment.wspay', compact('data'));
    }


    /**
     * @param Order $order
     * @param null  $request
     *
     * @return bool
     */
    public function finishOrder(Order $order, Request $request): bool
    {
        $reference = $request->input('ShoppingCartID');
        $signature = $request->input('Signature');
        $approval = $request->input('ApprovalCode');

        if ($order->payment_code !== 'wspay' || ! is_string($reference) || ! is_string($signature)
            || ! is_scalar($approval) || (string) $approval === '' || (string) $request->input('Success') !== '1'
            || ! preg_match('/^' . preg_quote((string) $order->id, '/') . '-[0-9]{4}-[A-Za-z0-9]{8}$/D', $reference)) {
            return false;
        }

        $settings = $this->paymentSettings();
        if (! $settings || empty($settings->data->secret_key) || empty($settings->data->shop_id)) {
            return false;
        }

        // https://docs.wspay.info/wspayform — ReturnURL signature.
        $expected = hash('sha512', $settings->data->shop_id . $settings->data->secret_key
            . $reference . $settings->data->secret_key . '1' . $settings->data->secret_key
            . (string) $approval . $settings->data->secret_key);

        if (! hash_equals($expected, strtolower($signature))) {
            return false;
        }

        $attempt = Transaction::where('order_id', $order->id)->where('pg_order_id', $reference)
            ->where('success', 0)->first();

        if (! $attempt || ! app(CheckoutCompletion::class)->sameAmount($attempt->amount, $order->total)
            || ! app(CheckoutCompletion::class)->sameAmount($request->input('Amount'), $attempt->amount)) {
            return false;
        }

        return app(CheckoutCompletion::class)->paid($order->id, 'wspay', [
            'amount' => $attempt->amount, 'signature' => $signature,
            'payment_type' => $request->input('PaymentType'),
            'payment_plan' => $request->input('PaymentPlan'),
            'payment_partner' => $request->input('Partner'),
            'datetime' => now(), 'approval_code' => (string) $approval,
            'pg_order_id' => $reference, 'lang' => 'EUR', 'stan' => $request->input('STAN'), 'error' => null,
        ]);
    }

    private function paymentSettings()
    {
        return \App\Models\Back\Settings\Settings::get('payment', 'list.wspay')->first();
    }
}
