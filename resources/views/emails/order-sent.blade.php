@extends('emails.layouts.base')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="ag-mail-tableset">{{ __('front/cart.pozdrav') }} {{ $order->payment_fname }}  {{ __('front/cart.hvala') }}</td>
        </tr>
        <tr>
            <td class="ag-mail-tableset"> <h3 style="line-height:0px">{{ __('front/cart.narudzba_broj') }}: {{ $order->id }} </h3></td>
        </tr>
        <tr>
            <td class="ag-mail-tableset">
                @include('emails.layouts.partials.order-details', ['order' => $order])
            </td>
        </tr>
        <tr>
            <td class="ag-mail-tableset">
                @include('emails.layouts.partials.order-price-table', ['order' => $order])
            </td>
        </tr>
        <tr>
            <td class="ag-mail-tableset">
                <b> {{ __('front/cart.nacin_placanja') }}:</b>
                @if ($order->payment_code == 'bank')
                    <b>{{ __('front/cart.bank') }}</b>

                    <p style="font-size:12px">{{ __('front/cart.sb1') }} {{ $order->id }} {{ __('front/cart.sb2') }}</p>
                    <p style="font-size:12px">{{ __('front/cart.sb3') }}.</p>

                    <p style="font-size:12px"> {{ __('front/cart.sb4') }}</p>

                    <p style="font-size:12px"> {{ __('front/cart.sb5') }}</p>

                    <p style="font-size:12px">{{ __('front/cart.sb6') }}  {{number_format($order->total, 2)}} €</p>


                    <p style="font-size:12px"> {{ __('front/cart.sb7') }}: HR98 2402 0061 1011 2296 1<br>
                        {{ __('front/cart.sb8') }}: {{ $order->id }}-{{date('ym')}}</p>


                    <p style="font-size:12px">{{ __('front/cart.sb9') }}</p>

                    <p><img src="{{ asset('media/img/qr/'.$order->id) }}.jpg" style="max-width:80%; border:1px solid #ccc; height:auto"></p>

                @elseif ($order->payment_code == 'cod')
                    <b>{{ __('front/cart.cod') }}</b>
                    <p style="font-size:12px">{{ __('front/cart.sb1') }} {{ $order->id }} {{ __('front/cart.sb2') }}.</p>
                @elseif ($order->payment_code == 'corvus')
                    <b>{{ __('CorvusPay') }}</b>
                    <p style="font-size:12px">{{ __('front/cart.sb1') }} {{ $order->id }} {{ __('front/cart.sb2') }}.</p>
                @elseif ($order->payment_code == 'wspay')
                    <b>{{ __('WSPay') }}</b>
                    <p style="font-size:12px">{{ __('front/cart.sb1') }} {{ $order->id }} {{ __('front/cart.sb2') }}.</p>
                @elseif ($order->payment_code == 'keks')
                    <b>{{ __('KeksPay') }}</b>
                    <p style="font-size:12px">{{ __('front/cart.sb1') }} {{ $order->id }} {{ __('front/cart.sb2') }}.</p>                @else
                    <b>{{ __('front/cart.ppp') }}</b>
                    <p style="font-size:12px">{{ __('front/cart.sb1') }} {{ $order->id }} {{ __('front/cart.sb2') }}.</p>                @endif
                <br><br>

                {{ __('front/cart.lp') }}<br>Rice Kakis | Asian Store
            </td>
        </tr>

    </table>
@endsection
