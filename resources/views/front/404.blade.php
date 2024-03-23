@extends('front.layouts.app')

@section('content')

    <div class="container py-5 mb-lg-3">
        <div class="row justify-content-center pt-lg-4 text-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <h1 class="display-404 py-lg-3">404</h1>
                <h2 class="h3 mb-4">{{ __('front/cart.404_text') }}</h2>
                <p class="fs-md mb-4">
                    <u>{{ __('front/cart.404_subtext') }}:</u>
                </p>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="row">
                    <div class="col-sm-4 mb-3">
                        <a class="card h-100 border-0 shadow-sm" href="{{ route('index') }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center"><i class="ci-home text-primary h4 mb-0"></i>
                                    <div class="ps-3">
                                        <h5 class="fs-sm mb-0">{{ __('front/ricekakis.homepage') }}</h5><span class="text-muted fs-ms">{{ __('front/cart.home_bck') }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-4 mb-3"><a class="card h-100 border-0 shadow-sm" href="{{ route('index') }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center"><i class="ci-search text-success h4 mb-0"></i>
                                    <div class="ps-3">
                                        <h5 class="fs-sm mb-0">{{ __('front/cart.search1') }}</h5><span class="text-muted fs-ms">{{ __('front/cart.search2') }}</span>
                                    </div>
                                </div>
                            </div></a></div>
                    <div class="col-sm-4 mb-3"><a class="card h-100 border-0 shadow-sm" href="{{ route('faq') }}">
                            <div class="card-body">
                                <div class="d-flex align-items-center"><i class="ci-help text-info h4 mb-0"></i>
                                    <div class="ps-3">
                                        <h5 class="fs-sm mb-0">{{ __('front/cart.faq1') }}</h5><span class="text-muted fs-ms">{{ __('front/cart.faq2') }} </span>
                                    </div>
                                </div>
                            </div></a></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js_after')
    @include('front.layouts.partials.recaptcha-js')
@endpush
