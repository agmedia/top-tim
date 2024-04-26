@extends('front.layouts.app')
@if (request()->routeIs(['index']))
    @section ( 'title', ' Top Tim - Better way to stay in the game' )
    @section ( 'description', 'Sportska oprema (Zeus, Joma, Givova, Adidas, Macron, Select...), tisak, marketing, promocija.' )


    @push('meta_tags')

        <link rel="canonical" href="{{ env('APP_URL')}}/{{ current_locale() }}" />
        <meta property="og:locale" content="hr_HR" />
        <meta property="og:site_name" content="Top Tim - Better way to stay in the game" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="Top Tim - Better way to stay in the game" />
        <meta property="og:description" content="Sportska oprema (Zeus, Joma, Givova, Adidas, Macron, Select...), tisak, marketing, promocija." />
        <meta property="og:url" content="{{ env('APP_URL')}}"  />
        <meta property="og:image" content="{{ asset('image/bannerd3-1.jpg') }}" />
        <meta property="og:image:secure_url" content="{{ asset('image/bannerd3-1.jpg') }}" />
        <meta property="og:image:width" content="1920" />
        <meta property="og:image:height" content="720" />
        <meta property="og:image:type" content="image/jpeg" />
        <meta property="og:image:alt" content="Top Tim - Better way to stay in the game" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Top Tim - Better way to stay in the game" />
        <meta name="twitter:description" content="Sportska oprema (Zeus, Joma, Givova, Adidas, Macron, Select...), tisak, marketing, promocija." />
        <meta name="twitter:image" content="{{ asset('image/bannerd3-1.jpg') }}" />

    @endpush

@else
    @section ( 'title', $page->title. ' | Top Tim - Better way to stay in the game' )
    @section ( 'description', $page->meta_description )
@endif

@section('content')

    @if (request()->routeIs(['index']))

    @include('front.layouts.partials.hometemp')

      <h1 style="visibility: hidden;height:1px "> Top Tim - Better way to stay in the game</h1>

        {!! $page->description !!}


      @push('js_after')
          <style>
              @media only screen and (max-width: 1040px) {
                  .scrolling-wrapper {
                      overflow-x: scroll;
                      overflow-y: hidden;
                      white-space: nowrap;
                      padding-bottom: 15px;
                  }
              }
          </style>
      @endpush


    @else



        <nav class="mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>{{ __('front/ricekakis.homepage') }}</a></li>
                <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $page->title }}</li>
            </ol>
        </nav>


        <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
            <h1 class="h2 mb-3 mb-md-0 me-3">{{ $page->title }}</h1>

        </section>



            <div class="mt-5 mb-5 fs-md" style="max-width:1240px">
                {!! $page->description !!}
            </div>


    @endif

@endsection
