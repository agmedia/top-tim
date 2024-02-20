@if (request()->routeIs(['naplata']) or request()->routeIs(['pregled']))


    <footer class="footer pt-5">


        <div class="wave-container-blue" style="background-color: #f6f9fc;"></div>
        <div class="bg-darker px-lg-5 py-3">
            <div class="d-sm-flex justify-content-between align-items-center mx-auto px-3" >
                <div class="fs-sm text-white  text-center text-sm-start py-3">Rice Kakis Asian Store © Sva prava pridržana. Web by <a class="text-white" href="https://www.agmedia.hr" target="_blank" rel="noopener">AG media</a></div>
                <div class="widget widget-links widget-light pt-1 text-center text-md-end"><img src="{{ asset('media/cards/visa.svg') }}" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/maestro.svg') }}" alt="Maestro" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/mastercard.svg') }}" alt="MasterCard" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/diners.svg') }}" alt="Diners" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"></div>
            </div>
        </div>
    </footer>

@else

    <section class="col">
        <div class="card py-5 border-0 " style="background: url({{ asset('image/china.jpg') }}) repeat center center fixed; background-size: contain;  ">
            <div class="card-body py-md-4 py-3 px-4 text-center">
                <h3 class="mb-3">Ne propusti akciju!</h3>
                <p class="mb-4 pb-2">Prijavi se na naš Newsletter i budi u toku sa najnovijim akcijama i novostima!</p>
                <div class="widget mx-auto" style="max-width: 500px;">
                    <form class="subscription-form validate" action="https://studio.us12.list-manage.com/subscribe/post?u=c7103e2c981361a6639545bd5&amp;amp;id=29ca296126" method="post" name="mc-embedded-subscribe-form" target="_blank" novalidate>
                        <div class="input-group flex-nowrap"><i class="ci-mail position-absolute top-50 translate-middle-y text-muted fs-base ms-3"></i>
                            <input class="form-control rounded-start" type="email" name="EMAIL" placeholder="Vaša email adresa" required>
                            <button class="btn btn-primary" type="submit" name="subscribe">Prijavi se*</button>
                        </div>
                        <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                        <div style="position: absolute; left: -5000px;" aria-hidden="true">
                            <input class="subscription-form-antispam" type="text" name="b_c7103e2c981361a6639545bd5_29ca296126" tabindex="-1">
                        </div>
                        <div class="form-text mt-3">* Prijavom na Newsletter pristajem na uvjete korištenja i dajem privolu za primanje promotivnih obavijesti.</div>
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
                                <h6 class="fs-base text-dark mb-1">Besplatna dostava HR</h6>
                                <p class="mb-0 fs-ms text-dark opacity-50">Besplatna dostava za narudžbe > 45€</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="d-flex"><i class="ci-gift link-primary" style="font-size: 2.25rem;"></i>
                            <div class="ps-3">
                                <h6 class="fs-base text-dark mb-1">Besplatna dostava Zagreb</h6>
                                <p class="mb-0 fs-ms text-dark opacity-50">Besplatna dostava za narudžbe > 25€</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="d-flex"><i class="ci-rocket link-primary" style="font-size: 2.25rem;"></i>
                            <div class="ps-3">
                                <h6 class="fs-base text-dark mb-1">Brza i pozdana dostava</h6>
                                <p class="mb-0 fs-ms text-dark opacity-50">Dostavljamo za 1-3 radna dana u Hrvatskoj</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="d-flex"><i class="ci-card link-primary" style="font-size: 2.25rem;"></i>
                            <div class="ps-3">
                                <h6 class="fs-base text-dark mb-1">Sigurna online kupnja</h6>
                                <p class="mb-0 fs-ms text-dark opacity-50">Stranica zaštićena SSL certifikatom</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="hr-light mb-4">
                <div class="row py-lg-3">
                    <div class="col-lg-4 mb-lg-0 mb-4">
                        <div class="widget pb-3 mb-lg-4">
                            <h3 class="widget-title text-dark pb-1">Rice Kakis Asian Store</h3>
                            <ul class="widget-list">
                            <li  class="widget-list-item"><span class="widget-list-link">Vukoje Logistika j.d.o.o.</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">Kaštelanska 4a. Veliko Polje, 10010 Zagreb</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">MBS: 081362286 - OIB: 04676029695</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">Žiro račun: Privredna banka Zagreb d.d.</span></li>
                                <li  class="widget-list-item"> <span class="widget-list-link">IBAN: HR9223400091111126783</span></li>
                            </ul>
                        </div>

                    </div>
                    <div class="col-lg-4 mb-lg-0 mb-4">

                        <div class="widget pb-3 mb-lg-4">
                                <h3 class="widget-title text-dark">Uvjeti kupnje</h3>
                                <ul class="widget-list">
                                    @foreach ($uvjeti_kupnje->sortBy('title') as $page)
                                        <li class="widget-list-item"><a class="widget-list-link" href="{{ route('catalog.route.page', ['page' => $page]) }}">{{ $page->title }}</a></li>
                                    @endforeach
                                </ul>
                            </div>

                    </div>
                    <div class="col-lg-4 mb-lg-0 mb-4">
                        <div class="widget pb-3 mb-lg-2">
                                <h3 class="widget-title text-dark pb-1">Kontaktirajte nas</h3>
                                <ul class="widget-list">
                                    <li  class="widget-list-item"><span class="widget-list-link"><a class="nav-link-style fs-md" href="tel:+385 99 3334448"><i class="ci-phone me-1"></i> +385 99 3334448 (HRV)</a></span></li>
                                    <li  class="widget-list-item"><span class="widget-list-link"> <a class="nav-link-style fs-md" href="tel:+385 91 5207047"><i class="ci-phone hme-1"></i> +385 91 5207047 (ENG)</a></span></li>
                                    <li  class="widget-list-item"><span class="widget-list-link"> <a class="nav-link-style fs-md" href="mailto:info@ricekakis.com"><i class="ci-mail  me-1"></i> info@ricekakis.com</a></span></li>

                                </ul>



                    </div>
                        <div><a class="btn-social bs-outline bs-facebook me-2 mb-2" href="https://www.facebook.com/ricekakis"><i class="ci-facebook"></i></a><a class="btn-social bs-outline bs-instagram me-2 mb-2" href="https://www.instagram.com/ricekakis/"><i class="ci-instagram"></i></a><a class="btn-social bs-outline bs-youtube me-2 mb-2" href="https://www.youtube.com/channel/UCdNEYWHea1pKfUJbKF6fU4g"><i class="ci-youtube"></i></a></div>

                        </div>

                </div>
            </div>
        </div>

        <div class="wave-container-blue"></div>
        <div class="bg-darker px-lg-5 py-3">
            <div class="d-sm-flex justify-content-between align-items-center mx-auto px-3" >
                <div class="fs-sm text-white  text-center text-sm-start py-3">Rice Kakis Asian Store © Sva prava pridržana. Web by <a class="text-white" href="https://www.agmedia.hr" target="_blank" rel="noopener">AG media</a></div>
                <div class="widget widget-links widget-light pt-1 text-center text-md-end">



                    <img src="{{ asset('img/logici/ws.svg') }}" loading="lazy" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px; " width="55" height="35">
                <img src="{{ asset('media/cards/visa.svg') }}" loading="lazy" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/maestro.svg') }}" loading="lazy" alt="Maestro" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/mastercard.svg') }}" loading="lazy" alt="MasterCard" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/diners.svg') }}" alt="Diners" class="d-inline-block" loading="lazy" style="width: 55px; margin-right: 3px;" width="55" height="35">

                </div>
            </div>
        </div>
    </footer>



@endif






