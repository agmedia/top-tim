

    <section class="col">
        <div class="card py-5 border-0 " style="background: url({{ asset('image/pattern3.jpg') }}) #fff repeat center center fixed; background-size: auto;  ">
            <div class="card-body py-md-4 py-3 px-4 text-center">
                <h3 class="mb-3">{{ __('front/ricekakis.newsletter_title') }}</h3>
                <p class="mb-4 pb-2">{{ __('front/ricekakis.newsletter_description') }}</p>
                <div class="widget mx-auto" style="max-width: 500px;">
                    <form class="subscription-form validate" action="" method="post" name="mc-embedded-subscribe-form" target="_blank" novalidate>
                        <div class="input-group flex-nowrap"><i class="ci-mail position-absolute top-50 translate-middle-y text-muted fs-base ms-3"></i>
                            <input class="form-control rounded-start" type="email" name="EMAIL" placeholder="{{ __('front/ricekakis.newsletter_email') }}" required>
                            <button class="btn btn-primary" type="submit" name="subscribe">{{ __('front/ricekakis.newsletter_btn') }}</button>
                        </div>
                        <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                        <div style="position: absolute; left: -5000px;" aria-hidden="true">
                            <input class="subscription-form-antispam" type="text" name="b_c7103e2c981361a6639545bd5_29ca296126" tabindex="-1">
                        </div>
                        <div class="form-text mt-3">{{ __('front/ricekakis.newsletter_foot') }}</div>
                        <div class="subscription-status"></div>
                    </form>
                </div>
            </div>
        </div>
    </section>


    <footer class="footer bg-light pt-3">

        <div class="px-lg-5 pt-2 pb-4">
            <div class="mx-auto px-3" >

                <div class="row py-lg-3 ">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="d-flex"><i class="ci-gift link-primary" style="font-size: 2.25rem;"></i>
                            <div class="ps-3">
                                <p class="fs-base fw-medium text-dark mb-1">{{ __('front/ricekakis.icons_t1') }}</p>
                                <p class="mb-0 fs-ms text-dark opacity-80">{{ __('front/ricekakis.icons_d1') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="d-flex"><i class="ci-coins link-primary" style="font-size: 2.25rem;"></i>
                            <div class="ps-3">
                                <p class="fs-base fw-medium text-dark mb-1">{{ __('front/ricekakis.icons_t2') }}</p>
                                <p class="mb-0 fs-ms text-dark opacity-80">{{ __('front/ricekakis.icons_d2') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="d-flex"><i class="ci-rocket link-primary" style="font-size: 2.25rem;"></i>
                            <div class="ps-3">
                                <p class="fs-base fw-medium text-dark mb-1">{{ __('front/ricekakis.icons_t3') }}</p>
                                <p class="mb-0 fs-ms text-dark opacity-80">{{ __('front/ricekakis.icons_d3') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="d-flex"><i class="ci-card link-primary" style="font-size: 2.25rem;"></i>
                            <div class="ps-3">
                                <p class="fs-base fw-medium text-dark mb-1">{{ __('front/ricekakis.icons_t4') }}</p>
                                <p class="mb-0 fs-ms text-dark opacity-80">{{ __('front/ricekakis.icons_d4') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="hr-light mb-4">
                <div class="row py-lg-3">
                    <div class="col-lg-4 mb-lg-0 mb-4">
                        <div class="widget pb-3 mb-lg-4">
                            <h3 class="widget-title text-dark pb-1">Top Tim d.o.o.</h3>
                            <ul class="widget-list">

                            <li  class="widget-list-item"><span class="widget-list-link">Put Gvozdenova 283, 22000 Šibenik </span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">OIB: 20925110769</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">{{ __('front/ricekakis.ziro_racun') }}: OTP banka d.d.</span></li>
                                <li  class="widget-list-item"> <span class="widget-list-link">IBAN: HR5824070001100095118 </span></li>


                            </ul>
                        </div>


                    </div>
                    <div class="col-lg-4 mb-lg-0 mb-4">

                        <div class="widget pb-3 mb-lg-4">
                                <h3 class="widget-title text-dark">{{ __('front/ricekakis.terms') }}</h3>

                                <ul class="widget-list">
                                    @if (isset($pages) && $pages)
                                        @foreach($pages as $page)
                                            @if($page->translation->title !='Homepage' and $page->group =='page' )
                                            <li class="widget-list-item"><a class="widget-list-link" href="{{ current_locale() }}/info/{{ $page->translation->slug }}">{{ $page->translation->title }}</a>
                                            </li>
                                            @endif
                                        @endforeach
                                    @endif
                                        <li class="widget-list-item"><a class="widget-list-link" href="{{ current_locale() }}/kontakt">{{ __('front/ricekakis.kontaktirajte_nas') }}</a>
                                        </li>
                                </ul>
                            </div>

                    </div>
                    <div class="col-lg-4 mb-lg-0 mb-4">
                        <div class="widget pb-3 mb-lg-2">
                                <h3 class="widget-title text-dark pb-1">{{ __('front/ricekakis.kontaktirajte_nas') }}</h3>
                                <ul class="widget-list">
                                    <li  class="widget-list-item"><span class="widget-list-link"><a class="nav-link-style fs-md" href="tel:+385 22 337000"><i class="ci-phone me-1"></i> +385 22 337000 </a></span></li>

                                    <li  class="widget-list-item"><span class="widget-list-link"> <a class="nav-link-style fs-md" href="mailto:info@top-tim.com"><i class="ci-mail  me-1"></i> info@top-tim.com</a></span></li>

                                </ul>



                    </div>
                        <div><a class="btn-social bs-outline bs-facebook me-2 mb-2" href="https://www.facebook.com/toptim1/" aria-label="Facebook"><i class="ci-facebook"></i></a><a class="btn-social bs-outline bs-instagram me-2 mb-2" aria-label="Instagram" href="https://www.instagram.com/toptimofficial/"><i class="ci-instagram"></i></a><a class="btn-social btn-x bs-outline bs-twitter me-2 mb-2" style="margin-top:-5px" aria-label="Twitter" href="https://twitter.com/toptimsport"><img src="{{ asset('image/x-icom.svg') }}"  class="x-icon"   alt="Top Tim - Better way to stay in the game" ></a></div>

                        </div>

                </div>
            </div>
        </div>


        <div class="bg-darker px-lg-5 py-3">
            <div class="d-sm-flex justify-content-between align-items-center mx-auto px-3" >
                <div class="fs-sm text-white  text-center text-sm-start py-3">Top Tim d.o.o. © {{ __('front/ricekakis.sva_prava') }}. Web by <a class="text-white" href="https://www.agmedia.hr" target="_blank" rel="noopener">AG media</a></div>
                <div class="widget widget-links widget-light pt-1 text-center text-md-end">



                    <img src="{{ asset('img/logici/ws.svg') }}" loading="lazy" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px; " width="55" height="35">
                <img src="{{ asset('media/cards/visa.svg') }}" loading="lazy" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/maestro.svg') }}" loading="lazy" alt="Maestro" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/mastercard.svg') }}" loading="lazy" alt="MasterCard" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/diners.svg') }}" alt="Diners" class="d-inline-block" loading="lazy" style="width: 55px; margin-right: 3px;" width="55" height="35">

                </div>
            </div>
        </div>
    </footer>










