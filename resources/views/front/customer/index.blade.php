@extends('front.layouts.app')

@section('content')

    @include('front.customer.layouts.header')

    <div class="container pb-5 mb-2 mb-md-4">
        <div class="row">
        @include('front.customer.layouts.sidebar')
        <!-- Content  -->
            <section class="col-lg-8">
                <!-- Toolbar-->
                <div class="d-none d-lg-flex justify-content-between align-items-center pt-lg-3 pb-4 pb-lg-5 mb-lg-3">
                    <h6 class="fs-base text-primary mb-0">{{ __('front/cart.edit_data') }}:</h6>
                    <a class="btn btn-primary btn-sm" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ci-sign-out me-2"></i>{{ __('front/cart.odjava') }}
                    </a>
                </div>

                @include('front.layouts.partials.session')
                <form action="{{ route('moj-racun.snimi', ['user' => $user]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{ method_field('PATCH') }}

                    <div class="row ">
                        <div class="col-sm-12">
                            <h2 class="h6 pt-1 pb-3 mb-3 border-bottom">{{ __('front/cart.osnovni_podaci') }}</h2>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="checkout-fn">{{ __('front/cart.name') }}</label>
                                <input class="form-control @error('fname') is-invalid @enderror" type="text" name="fname" value="{{ $user->details->fname }}">
                                @error('fname') <div id="val-username-error" class="invalid-feedback animated fadeIn">{{ __('front/cart.name_warning') }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="checkout-ln">{{ __('front/cart.surname') }}</label>
                                <input class="form-control @error('lname') is-invalid @enderror" type="text" name="lname" value="{{ $user->details->lname }}">
                                @error('lname') <div id="val-username-error" class="invalid-feedback animated fadeIn">{{ __('front/cart.surname_warning') }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="checkout-email">{{ __('front/cart.email') }}</label>
                                <input class="form-control @error('email') is-invalid @enderror" type="email" readonly name="email" value="{{ $user->email }}">
                                @error('email') <div id="val-username-error" class="invalid-feedback animated fadeIn">{{ __('front/cart.email_warning') }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="checkout-phone">{{ __('front/cart.telefon') }}</label>
                                <input class="form-control" type="text" name="phone" value="{{ $user->details->phone }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <h2 class="h6 pt-1 pb-3 mb-3 border-bottom">{{ __('front/cart.adresa_dostave') }}</h2>
                        </div>
                        <div class="col-sm-6">
                            <div class="col-sm-12">
                                <div class="mb-3">
                                    <label class="form-label" for="checkout-address">{{ __('front/cart.adresa') }}</label>
                                    <input class="form-control @error('address') is-invalid @enderror" type="text" name="address" value="{{ $user->details->address }}">
                                    @error('address') <div id="val-username-error" class="invalid-feedback animated fadeIn">{{ __('front/cart.adresa_warning') }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="checkout-city">{{ __('front/cart.grad') }}</label>
                                <input class="form-control @error('city') is-invalid @enderror" type="text" name="city" value="{{ $user->details->city }}">
                                @error('city') <div id="val-username-error" class="invalid-feedback animated fadeIn">{{ __('front/cart.grad_warning') }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="checkout-zip">{{ __('front/cart.zip') }}</label>
                                <input class="form-control @error('zip') is-invalid @enderror" type="text" name="zip" value="{{ $user->details->zip }}">
                                @error('zip') <div id="val-username-error" class="invalid-feedback animated fadeIn">{{ __('front/cart.zip_warning') }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3" wire:ignore>
                                <label class="form-label" for="checkout-country">{{ __('front/cart.drzava') }}</label>
                                <select class="form-select g @error('state') is-invalid @enderror" id="checkout-country" name="state">
                                    <option value=""></option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country['name'] }}" {{ $country['name'] == $user->details->state ? 'selected' : '' }}>{{ $country['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('state') <div id="val-username-error" class="invalid-feedback animated fadeIn">{{ __('front/cart.drzava_warning') }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="checkout-company">{{ __('front/cart.tvrtka') }}</label>
                                <input class="form-control" type="text" name="company" value="{{ $user->details->company }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <div class="mb-3">
                                    <label class="form-label" for="checkout-oib">{{ __('front/cart.oib') }}</label>
                                    <input class="form-control" type="text" name="oib" value="{{ $user->details->oib }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <button type="submit" class="btn btn-primary d-block w-100">{{ __('front/cart.save') }}</button>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>


@endsection
