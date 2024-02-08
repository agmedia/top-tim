@if (request()->routeIs(['naplata']) or request()->routeIs(['pregled']))


    <footer class="footer pt-5">


        <div class="wave-container-blue" style="background-color: #f6f9fc;"></div>
        <div class="bg-darker px-lg-5 py-3">
            <div class="d-sm-flex justify-content-between align-items-center mx-auto px-3" >
                <div class="fs-sm text-light opacity-50 text-center text-sm-start py-3">Plava krava © Sva prava pridržana. Web by <a class="text-light" href="https://www.agmedia.hr" target="_blank" rel="noopener">AG media</a></div>
                <div class="widget widget-links widget-light pb-4 text-center text-md-end"><img src="{{ asset('media/cards/visa.svg') }}" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/maestro.svg') }}" alt="Maestro" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/mastercard.svg') }}" alt="MasterCard" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/diners.svg') }}" alt="Diners" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"></div>
            </div>
        </div>
    </footer>

@else

    <div class="wave-container"></div>
    <footer class="footer bg-light pt-5">
        <div class="container-fluid pt-lg-1 px-lg-5 pt-2 pb-1 ">
            <div class="row pt-lg-2 text-left px-3 px-sm-1">

                <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter"><div class="d-inline-flex align-items-top-center text-start"><i class="ci-book text-primary" style="font-size: 2.6rem;"></i> <div class="ps-3"><p class="text-dark fw-bold fs-base mb-1">Preko 20000 naslova</p> <p class="text-dark fs-ms opacity-70 mb-0">Širok i eklektičan izbor knjiga</p></div></div></div>

                <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter"><div class="d-inline-flex align-items-top-center text-start"><i class="ci-gift text-primary" style="font-size: 2.6rem;"></i> <div class="ps-3"><p class="text-dark fw-bold fs-base mb-1">Besplatna dostava</p> <p class="text-dark fs-ms opacity-70 mb-0">Za narudžbe iznad 50 €</p></div></div></div>


                <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter">

                    <div class="d-inline-flex align-items-top-center text-start"><i class="ci-truck text-primary" style="font-size: 2.6rem;"></i> <div class="ps-3"><p class="text-dark fw-bold fs-base mb-1">Brza dostava</p> <p class="text-dark fs-ms opacity-70 mb-0">GLS - naš partner u dostavi</p></div></div></div>

                <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter"><div class="d-inline-flex align-items-top-center text-start"><i class="ci-security-check text-primary" style="font-size: 2.6rem;"></i> <div class="ps-3"><p class="text-dark fw-bold fs-base mb-1">Sigurna kupovina</p> <p class="text-dark fs-ms opacity-70 mb-0">SSL certifikat i CorvusPay</p></div></div></div>





            </div>
        </div>
        <div class="px-lg-5 pt-2 pb-4">
            <div class="mx-auto px-3" >
                <div class="row py-lg-4">
                    <div class="col-lg-4 mb-lg-0 mb-4">
                        <div class="widget pb-3 mb-lg-4">
                            <h3 class="widget-title text-dark pb-1">O nama</h3>
                            <ul class="widget-list">
                            <li  class="widget-list-item"><span class="widget-list-link">Plava krava izdavaštvo d.o.o., Nova cesta 130, 10000 Zagreb</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">Registarski sud: Trgovački sud u Zagrebu</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">Temeljni kapital: 20.000,00 kn uplaćen u cjelosti.</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">MBS: 081437207 - OIB: 79503141656</span></li>
                            <li  class="widget-list-item"><span class="widget-list-link">Žiro račun: Erste&amp;Steiermärkische Bank d.d.</span></li>
                                <li  class="widget-list-item"> <span class="widget-list-link">IBAN: HR98 2402 0061 1011 2296 1</span></li>
                            </ul>
                        </div>
                        <div><a class="btn-social bs-dark bs-facebook me-2 mb-2" aria-label="FAacebook" href="https://www.facebook.com/plavakravahr"><i class="ci-facebook"></i></a><a class="btn-social bs-dark bs-instagram me-2 mb-2" aria-label="Instagram" href="https://www.instagram.com/plavakravaofficial/"><i class="ci-instagram"></i></a></div>
                    </div>
                    <div class="col-lg-7 offset-lg-1">
                        <div class="d-flex flex-sm-row flex-column justify-content-sm-between mt-n4 mx-lg-n3">

                            <div class="widget widget-links widget-dark mt-4 px-lg-3 px-sm-n2">
                                <h3 class="widget-title text-dark">Uvjeti kupnje</h3>
                                <ul class="widget-list">
                                    @foreach ($uvjeti_kupnje->sortBy('title') as $page)
                                        <li class="widget-list-item"><a class="widget-list-link" href="{{ route('catalog.route.page', ['page' => $page]) }}">{{ $page->title }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="widget widget-links widget-dark mt-4 px-lg-3 px-sm-n2">
                                <h3 class="widget-title text-dark">Načini plaćanja</h3>
                                <ul class="widget-list">
                                    <li class="widget-list-item"><a href="#" class="widget-list-link"> Kreditnom karticom jednokratno ili na rate</a></li>
                                    <li class="widget-list-item"><a href="#" class="widget-list-link"> Virmanom / općom uplatnicom / internet bankarstvom</a></li>
                                    <li class="widget-list-item"><a href="#" class="widget-list-link">Gotovinom prilikom pouzeća</a></li>
                                </ul>
                                <div class="mt-3">
                                    <img src="{{ asset('img/logici/CorvusPAY.svg') }}" loading="lazy" alt="CorvusPay" class="d-inline-block" style="height: 40px; margin-right: 3px;" height="40" width="163px">
                                </div>
                                <div class="mt-3">
                                    <img src="{{ asset('img/logici/PCIDSS-small-a63323dc7c.png') }}" loading="lazy" alt="PCIDSS" class="d-inline-block" style="height: 40px; margin-right: 3px;" height="40" width="100">
                                    <img src="{{ asset('img/logici/PCIDSS_Certified_Badge.png') }}" loading="lazy" alt="PCIDSS_Certified" class="d-inline-block" style="height: 60px; margin-right: 3px;" height="60" width="57">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wave-container-blue"></div>
        <div class="bg-darker px-lg-5 py-3">
            <div class="d-sm-flex justify-content-between align-items-center mx-auto px-3" >
                <div class="fs-sm text-light opacity-50 text-center text-sm-start py-3">Plava krava © Sva prava pridržana. Web by <a class="text-light" href="https://www.agmedia.hr" target="_blank" rel="noopener">AG media</a></div>
                <div class="widget widget-links widget-light pb-4 text-center text-md-end">

                    <!--   <a href="https://www.mastercard.hr/hr-hr/consumers/find-card-products/debit-cards/maestro-debit.html" target="_blank"><img src="https://plava-krava.agmedia.rocks/image/catalog/credit-cards/Maestro_logo.svg.png" style="height:35px;background-color:#fff; margin-right:3px" /></a> <a href="http://www.visa.com.hr/" target="_blank"><img src="https://plava-krava.agmedia.rocks/image/catalog/credit-cards/visa.png" style="height:35px; margin-right:3px;background-color:#fff " /></a> <a href="http://www.mastercard.com/" target="_blank"><img src="https://plava-krava.agmedia.rocks/image/catalog/credit-cards/Mastercard-logo.svg.png" style="height:35px; margin-right:3px;background-color:#fff " /></a> <a href="https://www.dinersclub.com/" target="_blank"><img src="https://plava-krava.agmedia.rocks/image/catalog/credit-cards/logo-diners-club-png.png" style="height:35px;background-color:#fff margin-right:3px; " /></a>-->


                <img src="{{ asset('media/cards/visa.svg') }}" loading="lazy" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/maestro.svg') }}" loading="lazy" alt="Maestro" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/mastercard.svg') }}" loading="lazy" alt="MasterCard" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/diners.svg') }}" alt="Diners" class="d-inline-block" loading="lazy" style="width: 55px; margin-right: 3px;" width="55" height="35">

                </div>
            </div>
        </div>
    </footer>



@endif






