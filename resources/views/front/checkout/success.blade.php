@extends('front.layouts.app')

@section('google_data_layer')
    @if (!empty($data['google_tag_manager']))
        <script>
            window.dataLayer = window.dataLayer || [];
            // GA4 preporuka: reset ecommerce prije novog eventa
            window.dataLayer.push({ ecommerce: null });

            // $data['google_tag_manager'] = TagManager::getGoogleSuccessDataLayer($order)
            window.dataLayer.push(@json($data['google_tag_manager']));
        </script>
    @endif
@endsection

@section('content')
    <div class="pb-5 mb-sm-4">
        <div class="pt-0">
            <div class="card py-3 mt-sm-3">
                <div class="card-body text-center">
                    <h2 class="h4 pb-3">{{ __('front/cart.vasa_narudzba_txt') }}</h2>

                    @if($data['order']['payment_code'] == 'bank')
                        <p>{{ __('front/cart.sb1') }}</p>
                        <p>{{ __('front/cart.sb4') }}</p>
                        <p>{{ __('front/cart.sb5') }}</p>
                        <p>
                            {{ __('front/cart.sb6') }}
                            {{ number_format($data['order']['total'], 2, '.', '') }} €
                            <br>
                            {{ __('front/cart.sb7') }}: HR7023900011101520911 <br>
                            {{ __('front/cart.sb8') }}: {{ $data['order']['id'] }}-{{ date('ym') }}
                        </p>
                        <p>{{ __('front/cart.sb9') }}</p>
                        <p><img src="{{ asset('media/img/qr/'.$data['order']['id']) }}.jpg" alt="QR"></p>
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
@push('js_after')
    <script>
        fbq('track', 'Purchase', {
            value: {{ number_format($data['order']['total'] ?? 0, 2, '.', '') }}, // točna decimalna točka, bez tisućica
            currency: 'EUR',
            content_ids: @json($data['ids'] ?? []), // pravi JS array
            content_type: 'product'
        });
    </script>
@endpush
