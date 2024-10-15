
@extends('front.layouts.app')

@push('css_after')
    @livewireStyles
@endpush

@section('content')

    <!-- Page title + breadcrumb-->
    <nav class="mb-4" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>{{ __('front/ricekakis.homepage') }}</a></li>
            <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ __('front/cart.potvrdite_narudzbu') }}</li>
        </ol>
    </nav>
    <!-- Content-->
    <!-- Sorting-->
    <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
        <h1 class="h2 mb-3 mb-md-0 me-3">{{ __('front/cart.potvrdite_narudzbu') }}</h1>

    </section>


    <div class="pb-5 mb-2 mt-5 mb-md-4">
        <div class="row">

            <section class="col-lg-8">

                <div class="row">

                    <section class="col-lg-12">
                        <div class="steps steps-dark pt-2 pb-3 mb-2">
                            <a class="step-item active" href="{{ route('kosarica') }}">
                                <div class="step-progress"><span class="step-count">1</span></div>
                                <div class="step-label"><i class="ci-cart"></i>{{ __('front/cart.kosarica') }}</div>
                            </a>
                            <a class="step-item active" href="{{ route('naplata', ['step' => 'podaci']) }}">
                                <div class="step-progress"><span class="step-count">2</span></div>
                                <div class="step-label"><i class="ci-user-circle"></i>{{ __('front/cart.podaci') }}</div>
                            </a>
                            <a class="step-item active" href="{{ route('naplata', ['step' => 'dostava']) }}">
                                <div class="step-progress"><span class="step-count">3</span></div>
                                <div class="step-label"><i class="ci-package"></i>{{ __('front/cart.dostava') }}</div>
                            </a>
                            <a class="step-item active" href="{{ route('naplata', ['step' => 'placanje']) }}">
                                <div class="step-progress"><span class="step-count">4</span></div>
                                <div class="step-label"><i class="ci-card"></i>{{ __('front/cart.placanje') }}</div>
                            </a>
                            <a class="step-item current active" href="{{ route('pregled') }}">
                                <div class="step-progress"><span class="step-count">5</span></div>
                                <div class="step-label"><i class="ci-check-circle"></i>{{ __('front/cart.pregledaj') }}</div>
                            </a>
                        </div>
                    </section>

                </div>

               <h2 class="h5 pt-1 pb-3 mb-3">{{ __('front/cart.pregledaj_potvrdi') }} </h2>
                <div class="rounded-3 p-4 mt-3" style="border: 1px solid rgb(218, 225, 231); background-color: rgb(255, 255, 255) !important;">
                <cart-view continueurl="{{ route('index') }}" checkouturl="{{ route('naplata') }}" buttons="false"></cart-view>

                <div class="bg-secondary rounded-3 px-4 pt-4 pb-2">
                    <div class="row">
                        <div class="col-sm-6">
                            <h4 class="h6">{{ __('front/cart.platitelj') }}:</h4>
                            <ul class="list-unstyled fs-sm">
                                @if (auth()->guest())
                                    <li><span class="text-muted">{{ __('front/cart.korisnik') }}:&nbsp;</span>{{ $data['address']['fname'] }} {{ $data['address']['lname'] }}</li>
                                    <li><span class="text-muted">{{ __('front/cart.adresa') }}:&nbsp;</span>{{ $data['address']['address'] }}, {{ $data['address']['zip'] }} {{ $data['address']['city'] }}, {{ $data['address']['state'] }}</li>
                                    <li><span class="text-muted">{{ __('front/cart.email') }}:&nbsp;</span>{{ $data['address']['email'] }}</li>
                                @else
                                    <li><span class="text-muted">{{ __('front/cart.korisnik') }}:&nbsp;</span>{{ auth()->user()->details->fname }} {{ auth()->user()->details->lname }}</li>
                                    <li><span class="text-muted">{{ __('front/cart.adresa') }}:&nbsp;</span>{{ auth()->user()->details->address }}, {{ auth()->user()->details->zip }} {{ auth()->user()->details->city }}, {{ $data['address']['state'] }}</li>
                                    <li><span class="text-muted">{{ __('front/cart.email') }}:&nbsp;</span>{{ auth()->user()->email }}</li>
                                @endif
                            </ul>
                        </div>
                        <div class="col-sm-6">
                            <h4 class="h6">{{ __('front/cart.dostaviti_na') }}:</h4>
                            <ul class="list-unstyled fs-sm">
                                <li><span class="text-muted">{{ __('front/cart.korisnik') }}:&nbsp;</span>{{ $data['address']['fname'] }} {{ $data['address']['lname'] }}</li>
                                <li><span class="text-muted">{{ __('front/cart.adresa') }}:&nbsp;</span>{{ $data['address']['address'] }}, {{ $data['address']['zip'] }} {{ $data['address']['city'] }}, {{ $data['address']['state'] }}</li>
                                <li><span class="text-muted">{{ __('front/cart.email') }}:&nbsp;</span>{{ $data['address']['email'] }}</li>
                            </ul>
                        </div>
                        <div class="col-sm-6">
                            <h4 class="h6">{{ __('front/cart.nacin_dostave') }}:</h4>
                            <ul class="list-unstyled fs-sm">
                                <li>
                                    <span class="text-muted">{{ $data['shipping']->title->{current_locale()} }} </span><br>
                                    {!! $data['shipping']->data->short_description->{current_locale()} !!}
                                </li>
                            </ul>
                        </div>
                        <div class="col-sm-6">


                            <h4 class="h6">{{ __('front/cart.nacin_placanja') }}:</h4>
                            <ul class="list-unstyled fs-sm">


                                <li>
                                    <span class="text-muted">{{ $data['payment']->title->{current_locale()} }} </span><br>
                                    {{ $data['payment']->data->short_description->{current_locale()} }}
                                </li>
                            </ul>
                        </div>

                        <div class="col-sm-12">


                            <h4 class="h6">{{ __('Komentar') }}:</h4>
                           <p> {{ $data['comment'] }}</p>

                        </div>


                    </div>
                </div>
                </div>
                <div class="d-none d-lg-flex pt-0 mt-3">
                    {!! $data['payment_form'] !!}
                </div>

            </section>

            <aside class="col-lg-4 pt-4 pt-lg-0 mb-3 ps-xl-5 d-block">
                <cart-view-aside route="pregled" continueurl="{{ route('index') }}" checkouturl="/"></cart-view-aside>
            </aside>
        </div>

        <div class="row d-lg-none">
            <div class="col-lg-8">
                {!! $data['payment_form'] !!}
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('front/cart.opci_uvjeti') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    @if (isset($pages) && $pages)
                        @foreach($pages as $page)
                            @if($page->translation->title !='Homepage' and $page->group =='page' )
                                {!! $page->description !!}
                            @endif
                        @endforeach
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('front/cart.zatvori') }}</button>

                </div>
            </div>
        </div>
    </div>

@endsection

@push('js_after')
    @if ($data['payment']->code == 'keks')
        <script type="text/javascript">
            let refreshTime = 3000;

            function checkKeksResponse() {
                $.ajax({
                    method: 'post',
                    url: '{{ route('keks.provjera') }}',
                    data: {
                        order_id: '{{ $data['id'] }}',
                        _token: "{{ csrf_token() }}"
                    },
                    dataType: 'json',
                    success: function(json) {
                        if (json.status) {
                            document.location = json.redirect;
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    },
                    complete: function (data) {
                        setTimeout(checkKeksResponse, refreshTime);
                    }
                });
            }

            setTimeout(checkKeksResponse, refreshTime);
        </script>
    @endif
@endpush
