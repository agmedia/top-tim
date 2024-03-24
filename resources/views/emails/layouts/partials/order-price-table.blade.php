@push('css')
    <style>
        #products, #totals {
            /*font-family: "Roboto", Arial, Helvetica, sans-serif;*/
            font-size: 13px;
            border-collapse: collapse;
            width: 100%;
        }

        #products td, #products th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        #totals td, #totals th {
            border: 1px solid #ddd;
            padding: 6px 8px;
        }

        #products tr:nth-child(even){background-color: #f2f2f2;}

        /*#products tr:hover {background-color: #ddd;}*/

        #products th {
            font-size: 15px;
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #EF4D48;
            color: white;
            border:none;
        }
    </style>
@endpush

<table id="products">
    <tr>
        <th>{{ __('front/cart.proizvod') }}</th>

        <th style="text-align: center;" width="15%">{{ __('front/cart.kol') }}</th>
        <th style="text-align: right;" width="20%">{{ __('front/cart.cijena') }}</th>
        <th style="text-align: right;" width="25%">{{ __('front/cart.ukupno') }}</th>
    </tr>
    @foreach ($order->products as $product)
        <tr>
            <td>{{ $product->translation->name }} </td>
            <td style="text-align: center;">{{ $product->quantity }}</td>
            <td style="text-align: right;">€ {{ number_format($product->price, 2, ',', '.') }}</td>
            <td style="text-align: right;">€ {{ number_format($product->total, 2, ',', '.') }}</td>
        </tr>
    @endforeach
</table>
<table id="totals">
    @foreach ($order->totals as $total)
        <tr>
            <td style="border-right: none; border-top: none;"></td>
            <td style="border-left: none; border-right: none; border-top: none;"></td>
            <td style="border-left: none; text-align: right; border-top: none; {{ $total->code == 'shipping' ? '' : 'font-weight: bold;' }}">{{ $total->title }}</td>
            @if ($order->shipping_state != 'Croatia' && $total->code == 'shipping')
                <td style="border-left: none; text-align: right;border-top: none; {{ $total->code == 'shipping' ? '' : 'font-weight: bold;' }}" width="20%">€ {{ number_format($total->value, 2, ',', '.') }}</td>
            @else
                <td style="border-left: none; text-align: right;border-top: none; {{ $total->code == 'shipping' ? '' : 'font-weight: bold;' }}" width="20%">€ {{ number_format($total->value, 2, ',', '.') }}</td>
            @endif
        </tr>
    @endforeach
</table>

<small style="text-align: right;"> {{ __('front/cart.pdv_ukljucen') }}
    @foreach ($order->totals as $total)
        @if($total->code == 'subtotal')
        €<strong>{{ number_format($total->value - ($total->value / 1.25 ), 2, ',', '.') }}</strong>  {{ __('front/cart.pdv_artikli') }}
    @elseif ($total->code == 'shipping')
        €<strong>{{number_format($total->value - ($total->value / 1.25 ), 2, ',', '.') }}</strong> {{ __('front/cart.pdv_dostava') }}
    @endif
    @endforeach
</small>

