@extends('front.layouts.app')

@section('content')




    <nav class="mb-4" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>{{ __('front/ricekakis.homepage') }}</a></li>
            <li class="breadcrumb-item text-nowrap active" aria-current="page">Kontakt</li>
        </ol>
    </nav>


    <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
        <h1 class="h2 mb-3 mb-md-0 me-3">Kontakt</h1>

    </section>



    <!-- Contact detail cards-->
    <section class=" pt-grid-gutter">
        <div class="row">

            @include('front.layouts.partials.success-session')

            <div class="col-12 col-sm-6 mb-5">

                        <h3 class=" mb-4">Impressum</h3>
                <p>
                    <b>Tvrtka:</b> TOP TIM d.o.o.<br>
                    <b>Sjedište:</b> Put Gvozdenova 283, 22000 Šibenik<br>
                    <b>OIB:</b> 20925110769<br>
                    <b>IBAN:</b> HR58 2407 0001 1000 9511 8, OTP Banka d.d.<br>
                    <b>SWIFT:</b> OTPVHR2X
                </p>
                <p>
                    <strong>E-mail:</strong> <a href="mailto:info@top-tim.com">info@top-tim.com</a>
                </p>
                <p>
                    <strong>Tel:</strong> <a href="tel:+38522337000">022/337-000</a>
                </p>
                <p>
                    <b>Trgovački sud:</b> Trgovački sud u Zadru - stalna služba u Šibeniku<br>
                    <b>Temeljni kapital:</b> 20.000,00 kuna / 2.654,46 euro (fiksni tečaj konverzije 7.53450) plaćen u cijelosti<br>
                    <b>Osnivači/članovi društva:</b> Tamara Strika, jedini član d.o.o. <br>
                    <b>Osoba ovlaštena za zastupanje:</b> Tamara Strika, član uprave
                </p>

            </div>

            <div class="col-12 col-sm-6 mb-5 ">
                <h2 class="h4 mb-4">Pošaljite upit</h2>
                <form action="{{ route('poruka') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-12">
                            <label class="form-label" for="cf-name">Vaše ime:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" name="name" id="cf-name" placeholder="">
                            @error('name')<div class="text-danger font-size-sm">Molimo upišite vaše ime!</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="cf-email">Email adresa:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="email" id="cf-email" placeholder="" name="email">
                            @error('email')<div class="invalid-feedback">Molimo upišite ispravno email adresu!</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="cf-phone">Broj telefona:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <input class="form-control" type="text" id="cf-phone" placeholder="" name="phone">
                            @error('phone')<div class="invalid-feedback">Molimo upišite broj telefona!</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="cf-message">Upit:&nbsp;@include('back.layouts.partials.required-star')</label>
                            <textarea class="form-control" id="cf-message" rows="6" placeholder="" name="message"></textarea>
                            @error('message')<div class="invalid-feedback">Molimo upišite poruku!</div>@enderror
                            <button class="btn btn-primary mt-4" type="submit">Pošaljite upit</button>
                        </div>
                    </div>
                    <input type="hidden" name="recaptcha" id="recaptcha">
                </form>
            </div>

        </div>
    </section>






@endsection

@push('js_after')
    @include('front.layouts.partials.recaptcha-js')
@endpush
