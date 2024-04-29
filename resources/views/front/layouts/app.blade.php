<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <title> @yield('title') </title>
    <!-- SEO Meta Tags-->
    <script src="https://cmp.osano.com/AzZnFEUBLxxEr3ft2/c4f24af9-1095-4c0c-b7fb-308fed667ce8/osano.js"></script>
    <meta name="description" content="@yield('description')">
    <meta name="author" content="Top Tim - Better way to stay in the game">
    @stack('meta_tags')
    <!-- Viewport-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <base href="{{ config('settings.images_domain') }}">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" media="screen" href="{{ asset('vendor/simplebar/dist/simplebar.min.css') }}"/>
    <link rel="stylesheet" media="screen" href="{{ asset('css/theme.css?v=2.4') }}">
    <!-- Favicon and Touch Icons-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ config('settings.images_domain') . 'favicon-32x32.png' }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ config('settings.images_domain') . 'favicon-32x32.png' }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ config('settings.images_domain') . 'favicon-16x16.png' }}">
    <link rel="manifest" href="{{ config('settings.images_domain') . 'site.webmanifest' }}">
    <link rel="mask-icon" href="{{ config('settings.images_domain') . 'safari-pinned-tab.svg' }}" color="#05519e">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">
    @stack('css_after')
    @if (config('app.env') == 'production')
        @yield('google_data_layer')
    @endif
    @if (isset($js_lang))
        <script>
            window.trans = {!! $js_lang !!};
            window.locale = "{{ current_locale() }}";
        </script>
    @endif
</head>
<!-- Body-->
<body class="bg-secondary">
<!-- Google Tag Manager (noscript) -->
<!-- End Google Tag Manager (noscript) -->
<div id="agapp">
    <div v-cloak>
        <div class="v-cloak--inline"> <!-- Parts that will be visible before compiled your HTML -->
            <div class="spinner"></div>
        </div>
        <div class="v-cloak--hidden">
        @include('front.layouts.partials.header')
            <main class="offcanvas-enabled ">
                <section class="ps-lg-4 pe-lg-3 pt-4 page-wrapper">
                    <div class="px-3 pt-2">
                       @yield('content')
                    </div>
                </section>
                @include('front.layouts.partials.footer')
                @include('front.layouts.partials.handheld')
            </main>
        </div>
    </div>
</div>
<!-- Back To Top Button-->
<a class="btn-scroll-top" aria-label="Scroll To Top" href="#top" data-scroll data-fixed-element><span class="btn-scroll-top-tooltip text-muted fs-sm me-2">Top</span><i class="btn-scroll-top-icon ci-arrow-up">   </i></a>
<!-- Sign in / sign up modal-->
@include('front.layouts.modals.login')
<!-- Vendor Styles including: Font Icons, Plugins, etc.-->
<link rel="stylesheet" media="screen" href="{{ asset(config('settings.images_domain') . 'css/tiny-slider.css?v=1.2') }}"/>
<!-- Vendor scrits: js libraries and plugins-->
<script src="{{ asset('js/jquery/jquery-2.1.1.min.js') }}"></script>
<script src="{{ asset('js/jquery.ihavecookies.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/simplebar/dist/simplebar.min.js') }}"></script>
<script src="{{ asset('vendor/tiny-slider/dist/min/tiny-slider.js?v=2.0') }}"></script>
<script src="{{ asset('vendor/smooth-scroll/dist/smooth-scroll.polyfills.min.js') }}"></script>
<script src="{{ asset('js/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
<script src="{{ asset('js/shufflejs/dist/shuffle.min.js') }}"></script>
<!-- Main theme script-->
<script src="{{ asset('js/cart.js?v=2.0.16') }}"></script>
<script src="{{ asset('js/theme.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('body').ihavecookies({
            delay: 600,
            expires: 90,
            onAccept: function(){
                var myPreferences = $.fn.ihavecookies.cookie();
            },
            uncheckBoxes: false
        });
    });
</script>
<script>
    $(() => {
        $('#search-input').on('keyup', (e) => {
            if (e.keyCode == 13) {
                e.preventDefault();
                $('search-form').submit();
            }
        })
    });
</script>
<script>
    const myModal = document.getElementById('signin-modal')
    myModal.addEventListener('show.bs.modal', (ev) => {
        let invoker = ev.relatedTarget
        let selected_tab = invoker.getAttribute("data-tab-id")
        const tab_btn = document.querySelector('#' + selected_tab)
        const tab = new bootstrap.Tab(tab_btn)
        tab.show()

        let head = document.getElementsByTagName('head')[0];
        let script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.sitekey') }}';
        head.appendChild(script);
        setInterval(() => {
            grecaptcha.ready(function() {
                grecaptcha.execute('{{ config('services.recaptcha.sitekey') }}', {action: 'register'}).then(function(token) {
                    if (token) {
                        document.getElementById('recaptcha').value = token;
                    }
                });
            });
        }, 270);
    })
</script>
@stack('js_after')
</body>
</html>
