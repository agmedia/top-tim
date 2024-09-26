<!-- Navbar-->


<header class="bg-primary shadow-sm fixed-top" data-fixed-element>
    <div class="navbar navbar-expand-lg navbar-dark py-0">
        <div class="container-fluid">
            <a class="navbar-brand d-none d-md-block me-1 flex-shrink-0 py-0" href="{{ route('index') }}">
                <div class="logo-bg" style="background-color:#fff;margin-left:-30px; padding: 0 0px 0 30px; ">
                    <img src="{{ asset('image/logo-top-tim.svg') }}" width="210"  alt="Top Tim - Better way to stay in the game" >
                    <span class="arrow"></span>
                </div>
            </a>
            <a class="navbar-brand p-0 d-md-none me-0" href="{{ route('index') }}">
                <div class="logo-bg py-2" style="background-color:#fff;margin-left:-15px; padding: 6px 5px 6px 5px; ">
                <img src="{{ asset('image/logo-top-tim.svg') }}"  width="150" alt="Top Tim - Better way to stay in the game">

                </div>
            </a>
            <!-- Search-->
            <form action="{{ route('pretrazi') }}" id="search-form-first" class="w-100 d-none d-lg-flex flex-nowrap mx-4" method="get">
                {{--  <div class="dropdown input-group"><i class="ci-search position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                   <input type="text" name="{{ config('settings.search_keyword') }}" class="form-control rounded-start w-100" placeholder="Type Here..." id="search_box" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" onkeyup="javascript:load_data(this.value)" />
                   <span id="search_result"></span>
               </div>--}}
                <div class="dropdown w-100">
            <div class="input-group "><i class="ci-search position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                   <input class="form-control rounded-start w-100" type="text" name="{{ config('settings.search_keyword') }}" value="{{ request()->query('pojam') ?: '' }}" placeholder="{{ __('front/ricekakis.search_products') }}" id="search_box" data-toggle="dropdown" aria-haspopup="true" autocomplete="off" aria-expanded="false" onkeyup="javascript:load_data(this.value)">
               </div>

                <div id="search_result" class="live-search"></div>
                </div>
            </form>
            <!-- Toolbar-->
            <div class="navbar-toolbar d-flex flex-shrink-0 align-items-center ms-xl-2">
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" aria-label="Open the menu" data-bs-target="#sideNav"><span class="navbar-toggler-icon" aria-hidden="true"></span></button><a class="navbar-tool d-flex d-lg-none" href="#searchBox" data-bs-toggle="collapse" aria-label="Search" role="button" aria-expanded="false" aria-controls="searchBox"><span class="navbar-tool-tooltip">{{ __('front/ricekakis.search') }}</span>
                    <div class="navbar-tool-icon-box"><i class="navbar-tool-icon ci-search"></i></div></a>

                @if(auth()->user())
                    <a class="navbar-tool d-none d-sm-flex ms-1 ms-lg-0 me-n1 me-lg-2" aria-label="My account" href="{{ route('login') }}" >
                        <div class="navbar-tool-icon-box"><i class="navbar-tool-icon ci-user"></i></div>
                        <div class="navbar-tool-text ms-n3"><small>{{ auth()->user()->details->fname }} {{ auth()->user()->details->lname }}</small>{{ __('front/ricekakis.my_account') }}</div>
                    </a>

                @else
                    <a class="navbar-tool d-none d-sm-flex ms-1 ms-lg-0 me-n1 me-lg-2" data-tab-id="pills-signin-tab" aria-label="{{ __('front/ricekakis.login') }}" href="signin-tab"  role="button" data-bs-toggle="modal" data-bs-target="#signin-modal">
                        <div class="navbar-tool-icon-box bg-red"><i class="navbar-tool-icon ci-user"></i></div>
                        <div class="navbar-tool-text ">{{ __('front/ricekakis.login') }}</div>
                    </a>
                @endif


                @include('front.layouts.partials.language-selector')

                <cart-nav-icon carturl="{{ route('kosarica') }}" checkouturl="{{ route('naplata') }}"></cart-nav-icon>

            </div>
        </div>
    </div>
    <!-- Search collapse-->
    <div class="collapse" id="searchBox">
        <div class="card pt-2 pb-4 border-0 rounded-0">
            <div class="container">
                <form action="{{ route('pretrazi') }}" id="search-form" method="get">
                    <div class="input-group"><i class="ci-search position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                        <input class="form-control rounded-start" type="text" name="{{ config('settings.search_keyword') }}" value="{{ request()->query('pojam') ?: '' }}" placeholder="{{ __('front/ricekakis.search_products') }}">
                        <button type="submit" class="btn btn-primary btn-lg fs-base"><i class="ci-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Sidebar menu-->
<aside class="offcanvas offcanvas-expand w-100 border-end zindex-lg-5 pt-lg-5 mt-3" id="sideNav" style="max-width: 18.875rem;">
    <div class="pt-2 d-none d-lg-block"></div>

    <div class="offcanvas-body px-0 pt-3 pb-0" data-simplebar>
        <div class="tab-content">
            <filter-view ids="{{ isset($ids) ? $ids : null }}"
                         group="kategorija-proizvoda"
                         cat="{{ isset($cat) ? $cat : null }}"
                         subcat="{{ isset($subcat) ? $subcat : null }}"
                         locale="{{ current_locale() }}">
            </filter-view>
            <!-- Menu-->
            <div class="sidebar-nav tab-pane fade" id="menu" role="tabpanel">
                <div class="widget widget-categories">
                    <div class="accordion" id="shop-menu">
                        <!-- Homepages-->
                      {{--   @foreach ($uvjeti_kupnje->sortBy('title') as $page)
                            <div class="accordion-item border-bottom">
                                <h3 class="accordion-header px-grid-gutter"><a class="nav-link-style d-block fs-md fw-normal py-3" href="{{ route('catalog.route.page', ['page' => $page]) }}"><span class="d-flex align-items-center">{{ $page->title }}</span></a></h3>
                            </div>
                        @endforeach--}}
                        @if (isset($pages) && $pages)
                            @foreach($pages as $page)
                                @if($page->translation->title !='Naslovnica' and $page->group =='page' )
                                    <div class="accordion-item border-bottom">
                                        <h3 class="accordion-header px-grid-gutter"><a class="nav-link-style d-block fs-md fw-normal py-3" href="{{ current_locale() }}/info/{{ $page->translation->slug }}"><span class="d-flex align-items-center">{{ $page->translation->title }}</span></a></h3>
                                    </div>
                                @endif
                            @endforeach
                                <div class="accordion-item border-bottom">
                                    <h3 class="accordion-header px-grid-gutter"><a class="nav-link-style d-block fs-md fw-normal py-3" href="{{ current_locale() }}/faq"><span class="d-flex align-items-center">{{ __('front/cart.faq') }}</span></a></h3>
                                </div>
                                <div class="accordion-item border-bottom">
                                    <h3 class="accordion-header px-grid-gutter"><a class="nav-link-style d-block fs-md fw-normal py-3" href="{{ current_locale() }}/kontakt"><span class="d-flex align-items-center">Kontakt</span></a></h3>
                                </div>
                                @if(auth()->user())
                                    <div class="accordion-item border-bottom">
                                        <h3 class="accordion-header px-grid-gutter"><a class="nav-link-style d-block fs-md fw-normal py-3" href="{{ route('login') }}"><span class="d-flex align-items-center"><i class=" ci-user me-2"></i> {{ __('front/ricekakis.my_account') }}</span></a></h3>
                                    </div>
                                @else
                                    <div class="accordion-item border-bottom">
                                        <h3 class="accordion-header px-grid-gutter"><a class="nav-link-style d-block fs-md fw-normal py-3" data-tab-id="pills-signin-tab" aria-label="{{ __('front/ricekakis.login') }}" href="signin-tab"  role="button" data-bs-toggle="modal" data-bs-target="#signin-modal"><span class="d-flex align-items-center"><i class=" ci-user me-2"></i> {{ __('front/ricekakis.login') }}</span></a></h3>
                                    </div>
                                @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer d-block px-grid-gutter pt-4 pb-3 mb-2">


     {{--   <p class="pt-2 fw-medium pb-1">{{ __('front/ricekakis.follow_us') }}</p><a class="btn-social bs-outline bs-facebook me-2 mb-2" href="https://www.facebook.com/toptim1/" aria-label="Facebook"><i class="ci-facebook"></i></a><a class="btn-social bs-outline bs-instagram me-2 mb-2" aria-label="Instagram" href="https://www.instagram.com/toptimofficial/"><i class="ci-instagram"></i></a><a class="btn-social btn-x bs-outline bs-twitter me-2 mb-2" style="margin-top:-5px" aria-label="Twitter" href="https://twitter.com/toptimsport"><img src="{{ asset('image/x-icom.svg') }}"  class="x-icon"   alt="Top Tim - Better way to stay in the game" ></a>
    --}}

    </div>
</aside>

@push('js_after')
    <script>
        function load_data(query) {
            if(query.length > 2) {
                console.log(query);

             let all =  '{{ route('pretrazi') }}' + '?pojam=' + query;

                $.ajax({
                    method: 'get',
                    url: '{{ route('api.front.autocomplete') }}' + '?pojam_api=' + query,

                    success: function(json) {
                        console.log(json);

                        if (json.length > 0) {
                            let html = '<table class="table products"> <tbody>';


                            json.forEach(function (item) {
                                html += '<tr><td class="image"><a href="' + item.url + '">' + '<img  width="80" alt="' + item.name + ' " src="' + item.image + '">' + '</td>' +
                                    '<td class="main"><a href="' + item.url + '">' + item.name + '</a> <br><small> ' + item.sku + '</small></td><td class="price"><div class="price"><span class="price">' + item.main_price_text + '</span></div>' +
                                    '</td></tr>';
                            });



                            html += '</tbody></table>';

                            html += '<div class="result-text"><a href="' + all  + '" class="view-all-results">Pogledaj sve rezultate </a> </div>';

                            document.getElementById('search_result').innerHTML = html;
                        } else {
                            let html = '<div class="result-text"><a href="#" class="view-all-results">Nema pronađenih rezultata</a></div>';

                            document.getElementById('search_result').innerHTML = html;
                        }

                        /*if (json.status) {
                            document.location = json.redirect;
                        }*/
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });
            }
            else {
                document.getElementById('search_result').innerHTML = '';
            }
        }
    </script>
@endpush
