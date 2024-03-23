@extends('emails.layouts.base')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="ag-mail-tableset">{{ __('front/cart.dobili_ste') }} - {{ $order->created_at }}</td>
        </tr>
        <tr>
            <td class="ag-mail-tableset"> <h3>{{ __('front/cart.narudzba_broj') }}: {{ $order->id }} </h3></td>
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
                {{ __('front/cart.nacin_placanja') }}:
                @if ($order->payment_code == 'bank')
                    <b>{{ __('front/cart.bank') }}</b>
                @elseif ($order->payment_code == 'cod')
                    <b>{{ __('front/cart.cod') }}</b>
                @elseif ($order->payment_code == 'corvus')
                    <b>{{ __('CorvusPay') }}</b>
                @elseif ($order->payment_code == 'wspay')
                    <b>{{ __('WSPay') }}</b>
                @elseif ($order->payment_code == 'keks')
                    <b>{{ __('KeksPay') }}</b>
                @else
                    <b>{{ __('front/cart.ppp') }}</b>
                @endif
                <br><br>

                {{ __('front/cart.lp') }}<br>Rice Kakis | Asian Store
            </td>
        </tr>


    </table>
@endsection
