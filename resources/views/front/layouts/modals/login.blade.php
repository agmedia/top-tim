<div class="modal fade" id="signin-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link fw-medium active" data-bs-target="#signin-tab"  id="pills-signin-tab" data-bs-toggle="tab" role="tab" aria-controls="signin-tab" aria-selected="true"><i class="ci-unlocked me-2 mt-n1"></i>{{ __('front/cart.prijava') }}</a></li>
                    <li class="nav-item"><a class="nav-link fw-medium" data-bs-target="#signup-tab" id="pills-signup-tab" data-bs-toggle="tab" role="tab" aria-controls="signup-tab" aria-selected="false"><i class="ci-user me-2 mt-n1"></i>{{ __('front/cart.registriraj_se') }}</a></li>
                </ul>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body tab-content py-4"  >
                <form method="POST" class="needs-validation tab-pane fade show active" action="{{ route('login') }}" autocomplete="off" novalidate id="signin-tab" aria-controls="pills-signin">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="si-email">{{ __('front/cart.email') }}</label>
                        <input class="form-control" type="email" id="si-email" name="email" placeholder="" required>
                        <div class="invalid-feedback">{{ __('front/cart.email_warning') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="si-password">{{ __('front/cart.lozinka') }}</label>
                        <div class="password-toggle">
                            <input class="form-control" type="password" name="password" id="si-password" required>
                            <label class="password-toggle-btn" aria-label="Show/hide password">
                                <input class="password-toggle-check" type="checkbox"><span class="password-toggle-indicator"></span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3 d-flex flex-wrap justify-content-between">
                        <div class="form-check mb-2 ps-0">
                            <x-jet-checkbox id="remember_me" name="remember" />
                            <label class="form-check-label" for="si-remember">{{ __('front/cart.zapamti') }}</label>
                        </div><!--<a class="fs-sm" href="#">Zaboravljena lozinka</a>-->
                    </div>
                    <button class="btn btn-primary btn-shadow d-block w-100" type="submit">{{ __('front/cart.prijavi_se') }}</button>
                </form>
                <form class="needs-validation tab-pane fade" method="POST" action="{{ route('register') }}" autocomplete="off" novalidate id="signup-tab"  aria-controls="pills-signup" oninput='password_confirmation.setCustomValidity(password_confirmation.value != password.value ? "Passwords do not match." : "")'>



                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="su-name">{{ __('front/cart.korisnicko_ime') }}</label>
                        <input class="form-control" type="text" name="name" id="su-name" placeholder="" required>
                        <div class="invalid-feedback">{{ __('front/cart.korisnicko_ime_warning') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="si-email">{{ __('front/cart.email') }}</label>
                        <input class="form-control" type="email" name="email"  id="su-email" placeholder="" required>
                        <div class="invalid-feedback">{{ __('front/cart.email_warning') }}</div>

                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="su-password">{{ __('front/cart.lozinka') }}</label>
                        <div class="password-toggle">
                            <input class="form-control" type="password" name="password" minlength="8" id="su-password" required>

                            <label class="password-toggle-btn" aria-label="Show/hide password">
                                <input class="password-toggle-check" type="checkbox"><span class="password-toggle-indicator"></span>
                            </label>
                        </div>
                        <div id="emailHelp" class="form-text">{{ __('front/cart.min_8') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="su-password-confirm">{{ __('front/cart.potvrdite_lozinku') }}</label>
                        <div class="password-toggle">
                            <input class="form-control" type="password" name="password_confirmation"  minlength="8" id="su-password-confirm" required>
                            <label class="password-toggle-btn" aria-label="Show/hide password">
                                <input class="password-toggle-check" type="checkbox"><span class="password-toggle-indicator"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-check form-check-inline">
                        <label class="form-check-label" for="ex-check-4">{{ __('front/cart.slazem_se_sa') }} {!! __(' :terms_of_service', [
                                                'terms_of_service' => '<a target="_blank" href="'.route('catalog.route.page',['page' => 'opci-uvjeti-kupnje']).'" class="link-fx">'.__('front/cart.uvijetima_kupovine').'</a>',
                                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="link-fx">'.__('Privacy Policy').'</a>',
                                        ]) !!}</label>
                        <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                        <div class="invalid-feedback" id="terms">{{ __('front/cart.morate_se_sloziti') }}</div>
                    </div>


                   {{-- @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <div class="form-group mb-3" >
                            <x-jet-label for="terms">
                                <div class="flex items-center">
                                    <x-jet-checkbox name="terms" id="terms"/>
                                    <label class="form-label">
                                        {!! __('Slažem se sa :terms_of_service', [
                                                'terms_of_service' => '<a target="_blank" href="'.route('catalog.route.page',['page' => 'opci-uvjeti-kupnje']).'" class="link-fx">'.__('Uvjetima kupovine').'</a>',
                                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="link-fx">'.__('Privacy Policy').'</a>',
                                        ]) !!}
                                    </label>
                                    <div class="invalid-feedback" id="terms">Morate se složiti sa Uvjetima kupnje.</div>
                                </div>
                            </x-jet-label>
                        </div>
                    @endif--}}



                    <button class="btn btn-primary btn-shadow d-block w-100" type="submit">{{ __('front/cart.registriraj_se') }}</button>

                    <input type="hidden" name="recaptcha" id="recaptcha">
                    <div class="mt-2 d-block"><small>{{ __('front/cart.ova_stranica') }}
                            <a href="https://policies.google.com/privacy">{{ __('front/cart.pravila') }}</a> {{ __('front/cart.i') }}
                            <a href="https://policies.google.com/terms">{{ __('front/cart.uvjeti_pruzanja') }}</a>.
                        </small>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>


