<div>
    <div class="steps steps-dark pt-2 pb-3 mb-4">
        <a class="step-item active" href="{{ route('kosarica') }}">
            <div class="step-progress"><span class="step-count">1</span></div>
            <div class="step-label"><i class="ci-cart"></i>{{ __('front/cart.kosarica') }}</div>
        </a>
        <a class="step-item @if($step == 'podaci') current @endif @if(in_array($step, ['podaci', 'dostava', 'placanje'])) active @endif" wire:click="changeStep('podaci')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">2</span></div>
            <div class="step-label"><i class="ci-user-circle"></i>{{ __('front/cart.podaci') }}</div>
        </a>
        <a class="step-item @if($step == 'dostava') current @endif @if(in_array($step, ['dostava', 'placanje'])) active @endif" wire:click="changeStep('dostava')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">3</span></div>
            <div class="step-label"><i class="ci-package"></i>{{ __('front/cart.dostava') }}</div>
        </a>
        <a class="step-item @if($step == 'placanje') current @endif @if(in_array($step, ['placanje'])) active @endif" wire:click="changeStep('placanje')" href="javascript:void(0);">
            <div class="step-progress"><span class="step-count">4</span></div>
            <div class="step-label"><i class="ci-card"></i>{{ __('front/cart.placanje') }}</div>
        </a>
        <a class="step-item" href="{{ ($payment != '') ? route('pregled') : '#' }}">
            <div class="step-progress"><span class="step-count">5</span></div>
            <div class="step-label"><i class="ci-check-circle"></i>{{ __('front/cart.pregledaj') }}</div>
        </a>
    </div>

    @if ( ! empty($gdl) && ! $gdl_shipping && ! $gdl_payment && $gdl_event)
        @section('google_data_layer')
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    'event': '<?php echo $gdl_event; ?>',
                    'ecommerce': {
                        'items': <?php echo json_encode($gdl); ?>
                    } });
            </script>
        @endsection
    @endif

    @if ( ! empty($gdl) && $gdl_shipping && $gdl_event == 'add_shipping_info')
        @section('google_data_layer')
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    'event': '<?php echo $gdl_event; ?>',
                    'ecommerce': {
                        'shipping_tier': '<?php echo $gdl_shipping; ?>',
                        'items': <?php echo json_encode($gdl); ?>
                    } });
            </script>
        @endsection
    @endif

    @if ( ! empty($gdl) && $gdl_payment && $gdl_event == 'add_payment_info')
        @section('google_data_layer')
            <script>
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    'event': '<?php echo $gdl_event; ?>',
                    'ecommerce': {
                        'payment_type': '<?php echo $gdl_payment; ?>',
                        'items': <?php echo json_encode($gdl); ?>
                    } });
            </script>
        @endsection
    @endif

    @if ($step == 'podaci')
        <h2 class="h5 pt-1 pb-3 mb-3 m">{{ __('front/cart.adresa_dostave') }}</h2>

        @if (session()->has('login_success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <small>{{ session('login_success') }}</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (auth()->guest())
            <div class="alert alert-secondary d-flex mb-3" role="alert">
                <div class="alert-icon">
                    <i class="ci-user"></i>
                </div>
                <div><a data-bs-toggle="collapse" href="#collapseLogin" role="button" aria-expanded="false" aria-controls="collapseLogin" class="alert-link">{{ __('front/cart.prijava') }} </a> {{ __('front/cart.za_registrirane_korisnike') }}</div>
            </div>

            @if (session()->has('error'))
                <div class="alert alert-primary alert-dismissible fade show" role="alert">
                    <small>  {{ session('error') }}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="collapseLogin" aria-expanded="false" class="collapse">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label" for="si-email">{{ __('front/cart.email') }} </label>
                                    <input class="form-control" type="email" wire:model.defer="login.email" placeholder="" required>
                                    <div class="invalid-feedback">{{ __('front/cart.email_warning') }} {{ __('front/cart.email_warning') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <label class="form-label" for="si-password">{{ __('front/cart.lozinka') }}</label>
                                    <div class="password-toggle">
                                        <input class="form-control" type="password" wire:model.defer="login.pass" required>
                                        <label class="password-toggle-btn" aria-label="Show/hide password">
                                            <input class="password-toggle-check" type="checkbox"><span class="password-toggle-indicator"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-3 d-flex flex-wrap justify-content-between">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model.defer="login.remember" id="si-remember">
                                        <label class="form-check-label" for="si-remember">{{ __('front/cart.zapamti_me') }}</label>
                                    </div>


                                    <button id="signup-button" data-tab-id="pills-signup-tab" type="button" href="signup-tab" class="btn btn-outline-primary  btn-sm float-end" data-bs-toggle="modal" data-bs-target="#signin-modal">
                                        {{ __('front/cart.registriraj_se') }}
                                    </button>


                                </div>
                                <button class="btn btn-primary btn-shadow d-block w-100" wire:click="authUser()" type="button">{{ __('front/cart.prijava') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-3 p-4 mt-3" style="border: 1px solid rgb(218, 225, 231); background-color: rgb(255, 255, 255) !important;">

        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-fn">{{ __('front/cart.name') }} <span class="text-danger">*</span></label>
                    <input name="fname" class="form-control @error('address.fname') is-invalid @enderror" type="text" wire:model.defer="address.fname">
                    @error('address.fname') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.name_warning') }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-ln">{{ __('front/cart.surname') }}<span class="text-danger">*</span></label>
                    <input name="lname" class="form-control @error('address.lname') is-invalid @enderror" type="text" wire:model.deferl="address.lname">
                    @error('address.lname') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.surname_warning') }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-email">{{ __('front/cart.email') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.email') is-invalid @enderror" type="email" wire:model.defer="address.email">
                    @error('address.email') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.email_warning') }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-phone">{{ __('front/cart.telefon') }} <span class="text-danger">*</span></label>
                    <input class="form-control @error('address.phone') is-invalid  @enderror" type="text" wire:model.defer="address.phone">
                    @error('address.phone') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.telefon_warning') }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-address">{{ __('front/cart.adresa') }} <span class="text-danger">*</span></label>
                    <input name="street-address" class="form-control @error('address.address') is-invalid @enderror" type="text" wire:model.defer="address.address">
                    @error('address.address') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.adresa_warning') }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-city">{{ __('front/cart.grad') }} <span class="text-danger">*</span></label>
                    <input name="city" class="form-control @error('address.city') is-invalid @enderror"  type="text" wire:model.defer="address.city">
                    @error('address.city') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.grad_warning') }}</div> @enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-label" for="checkout-zip">{{ __('front/cart.zip') }}<span class="text-danger">*</span></label>
                    <input name="postal-code" class="form-control @error('address.zip') is-invalid @enderror" type="text" wire:model.defer="address.zip">
                    @error('address.zip') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.zip_warning') }}</div> @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3" wire:ignore>
                    <label class="form-label" for="checkout-country">{{ __('front/cart.drzava') }} <span class="text-danger">*</span></label>
                    <select name="country" class="form-select @error('address.state') is-invalid @enderror" id="state-select" wire:model="address.state" wire:change="stateSelected($event.target.value)">
                        <option value=""></option>
                        @foreach ($countries as $country)
                            <option value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    @error('address.state') <div class="invalid-feedback animated fadeIn">{{ __('front/cart.drzava_warning') }}</div> @enderror
                </div>
            </div>
        </div>
                <div class="row">
                    <div class="col-sm-12">

                        <label for="textarea-input" class="form-label">{{ __('Komentar') }}</label>
                        <textarea class="form-control" name="comment" wire:model.defer="comment" id="textarea-input" placeholder="" rows="6"></textarea>

                        <div class="form-text">{{ __('Ukoliko trebate tisak ili gravuru na nekom od artikala onda to upišite ovdje') }}</div>
                    </div>


            </div>
        <h2 class="h6 pt-3 pb-3 mb-2"><a data-bs-toggle="collapse" href="#collapseOib" role="button" aria-expanded="false" aria-controls="collapseLogin" class="alert-link">{{ __('front/cart.trebate_r1') }}</a></h2>

        <div id="collapseOib" aria-expanded="false" class="collapse" wire:ignore>

            <div class="row ">
                <div class="col-sm-6">
                    <div class="mb-3">
                        <label class="form-label" for="checkout-company">{{ __('front/cart.tvrtka') }}</label>
                        <input class="form-control" name="company" type="text" wire:model="address.company">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="mb-3">
                        <div class="mb-3">
                            <label class="form-label" for="checkout-oib">{{ __('front/cart.oib') }}</label>
                            <input class="form-control" name="oib" type="text" wire:model="address.oib">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        </div>
        <div class="d-flex pt-4 mt-3">
            <div class="w-50 pe-3"><a class="btn btn-outline-primary d-block w-100" href="{{ route('kosarica') }}"><i class="ci-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">{{ __('front/cart.povratak_cart') }}</span><span class="d-inline d-sm-none">{{ __('front/cart.povratak') }}</span></a></div>
            <div class="w-50 ps-2"><a class="btn btn-primary d-block w-100" wire:click="changeStep('dostava')" href="javascript:void(0);"><span class="d-none d-sm-inline">{{ __('front/cart.na_odabir_dostave') }}</span><span class="d-inline d-sm-none">{{ __('front/cart.nastavi') }}</span><i class="ci-arrow-right mt-sm-0 ms-1"></i></a></div>
        </div>

    @endif


    @if ($step == 'dostava')
        <h2 class="h5 pt-1 pb-3 mb-3 ">{{ __('front/cart.odaberite_nacin_dostave') }}</h2>

        <div class="rounded-3 p-4 mt-3" style="border: 1px solid rgb(218, 225, 231); background-color: rgb(255, 255, 255) !important;">
        <div class="table-responsive">
            <table class="table table-hover fs-sm ">
                <thead>
                <tr class="bg-secondary ">

                    <th colspan="2" class="align-middle border-bottom-0">{{ __('front/cart.dostava') }}</th>
                    <th class="align-middle border-bottom-0">{{ __('front/cart.vrijeme_dostave') }}</th>
                    <th class="align-middle border-bottom-0">{{ __('front/cart.cijena') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($shippingMethods as $s_method)


                    <tr wire:click="selectShipping('{{ $s_method->code }}')" style="cursor: pointer;">
                        <td>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="radio" value="{{ $s_method->code }}" wire:model="shipping">
                                <label class="form-check-label" for="courier"></label>
                            </div>
                        </td>
                        <td class="align-middle"><span class="text-dark fw-medium">{{ $s_method->title->{current_locale()} }}</span><br><span class="text-muted">{!! $s_method->data->short_description->{current_locale()} !!}</span>
                            @if ($s_method->code == 'gls_eu' && $view_comment)
                                <input class="form-control mt-2" type="text" wire:model="comment" placeholder="">
                            @endif

                        </td>
                        <td class="align-middle">{{ $s_method->data->time }}</td>
                        <td class="align-middle">
                            @if ($is_free_shipping)
                                 0€
                                @if ($secondary_price)0kn
                                @endif
                            @else
                               {{ $s_method->data->price }}€
                                    @if ($secondary_price)
                                        <br>{{ $s_method->data->price ? number_format($s_method->data->price * $secondary_price, 2) : '0' }}kn
                                    @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($shippingMethods->isEmpty())
          <p class="text-danger">{!!__('front/cart.ne_vrsimo_dostavu')  !!} </p>
            @error('shipping') <small class="text-danger">{!!__('front/cart.ne_vrsimo_dostavu')  !!}  </small> @enderror
        @else
            @error('shipping') <small class="text-danger">{{ __('front/cart.dostava_obavezna') }}</small> @enderror
        @endif
        </div>
        <div class=" d-flex pt-4 mt-3">
            <div class="w-50 pe-3"><a class="btn btn-outline-primary d-block w-100" wire:click="changeStep('podaci')" href="javascript:void(0);"><i class="ci-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">{{ __('front/cart.povratak_na_unos_podataka') }}</span><span class="d-inline d-sm-none">{{ __('front/cart.povratak') }}</span></a></div>
            <div class="w-50 ps-2"><a class="btn btn-primary d-block w-100" wire:click="changeStep('placanje')" href="javascript:void(0);"><span class="d-none d-sm-inline">{{ __('front/cart.na_odabir_placanja') }}</span><span class="d-inline d-sm-none">{{ __('front/cart.nastavi') }}</span><i class="ci-arrow-right mt-sm-0 ms-1"></i></a></div>
        </div>
    @endif


    @if ($step == 'placanje')
        <h2 class="h5 pt-1 pb-3 mb-3 ">{{ __('front/cart.odaberite_nacin_placanja') }}</h2>
        <div class="rounded-3 p-4 mt-3" style="border: 1px solid rgb(218, 225, 231); background-color: rgb(255, 255, 255) !important;">
        <div class="table-responsive">
            <table class="table table-hover fs-sm no-border">

                <thead>
                <tr class="bg-secondary ">
                    <th colspan="2" class="align-middle border-bottom-0">{{ __('front/cart.odaberite_nacin_placanja') }}</th>



                </tr>
                </thead>

                <tbody>
                @foreach ($paymentMethods as $p_method)
                    <tr wire:click="selectPayment('{{ $p_method->code }}')" style="cursor: pointer;">
                        <td>
                            <div class="form-check mb-2  ">
                                <input class="form-check-input" type="radio" value="{{ $p_method->code }}" wire:model="payment">
                                <label class="form-check-label" for="courier"></label>
                            </div>
                        </td>
                        <td class="align-middle"><span class="text-dark fw-medium">{{ $p_method->title->{current_locale()} }}</span><br><span class="text-muted">{{ $p_method->data->short_description->{current_locale()} }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @error('payment') <small class="text-danger">{{ __('front/cart.nacin_placanja_obavezan') }}</small> @enderror
        </div>
        <div class=" d-flex pt-4 mt-3">
            <div class="w-50 pe-3"><a class="btn btn-outline-primary d-block w-100" wire:click="changeStep('dostava')" href="javascript:void(0);"><i class="ci-arrow-left mt-sm-0 me-1"></i><span class="d-none d-sm-inline">{{ __('front/cart.povratak_na_odabir_dostave') }}</span><span class="d-inline d-sm-none">{{ __('front/cart.povratak') }}</span></a></div>
            <div class="w-50 ps-2"><a class="btn btn-primary d-block w-100" href="{{ ($payment != '') ? route('pregled') : '#' }}"><span class="d-none d-sm-inline">{{ __('front/cart.pregledajte_narudzbu') }}</span><span class="d-inline d-sm-none">{{ __('front/cart.nastavi') }}</span><i class="ci-arrow-right mt-sm-0 ms-1"></i></a></div>
        </div>
    @endif

</div>


@push('js_after')

    <script>
        $(document).ready(function(){
            $(this).scrollTop(0);
        });
    </script>

@endpush
