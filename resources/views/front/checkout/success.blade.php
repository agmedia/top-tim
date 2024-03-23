
@extends('front.layouts.app')

@section('content')

    @if (isset($data['google_tag_manager']))

        <!-- Event snippet for Purchase conversion page -->
        <script>
            gtag('event', 'conversion', {
                'send_to': 'AW-11343209567/RcwNCISSyOMYEN_I7qAq',
                'transaction_id': ''
            });
        </script>

        @section('google_data_layer')
            <!-- Event snippet for Purchase conversion page --> <script> gtag('event', 'conversion', { 'send_to': 'AW-11343209567/RcwNCISSyOMYEN_I7qAq', 'transaction_id': '' }); </script>
            <script>
                window.dataLayer = window.dataLayer || [];
                dataLayer.push(<?php echo json_encode($data['google_tag_manager']); ?>);
            </script>
        @endsection
    @endif

    <div class="pb-5 mb-sm-4">
        <div class="pt-0">
            <div class="card py-3 mt-sm-3">
                <div class="card-body text-center">
                    <h2 class="h4 pb-3">{{ __('front/cart.vasa_narudzba_txt') }}</h2>

                    @if($data['order']['payment_code'] == 'bank')
                        <p>{{ __('front/cart.sb1') }} {{ $data['order']['id'] }} {{ __('front/cart.sb2') }}</p><p>{{ __('front/cart.sb3') }}</p>
                        <p> {{ __('front/cart.sb4') }}</p>
                        <p> {{ __('front/cart.sb5') }}</p>
                        <p>{{ __('front/cart.sb6') }}  {{number_format($data['order']['total'], 2)}} €<br>
                            {{ __('front/cart.sb7') }}: HR98 2402 0061 1011 2296 1<br>
                            {{ __('front/cart.sb8') }}: {{ $data['order']['id'] }}-{{date('ym')}}</p>
                        <p>{{ __('front/cart.sb9') }}</p>
                        <p><img src="{{ asset('media/img/qr/'.$data['order']['id']) }}.jpg"></p>
                    @else
                        <p class="fs-sm mb-2">{{ __('front/cart.se1') }}</p>
                        <p class="fs-sm">{{ __('front/cart.se2') }}</p>
                    @endif

                    <a class="btn btn-secondary mt-3 me-3" href="{{ route('index') }}">{{ __('front/cart.nastavite_pregled') }}</a>
                </div>
            </div>
        </div>
    </div>



@endsection
