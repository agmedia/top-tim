<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <title> @yield('title') </title>
    <!-- SEO Meta Tags-->
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

    <style>
        body{
            height: 100vh;
        }
        .container{
            height: 100%;
        }
    </style>
</head>
<body>
<!-- Document Wrapper -->
<div id="wrapper" class="clearfix">
    <!-- Topbar -->

<!-- Slider -->
{{-- @include('front.layouts.partials.slider') --}}
<!-- Content -->
@yield('content')
<!-- Footer -->

</div><!-- #wrapper end -->

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

</body>
</html>
