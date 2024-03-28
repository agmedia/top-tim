@extends('front.layouts.app')
@if(isset($receptis))
        @section ( 'title', 'Recepti - Rice Kakis | Asian Store' )
        @section ( 'description', 'Gastronomske poslastice vas očekuju: Uživajte u primamljivom mochiju, bubble tea-u, kimchiju, proizvodima bez glutena i ukusnim umacima.' )
@else
    @section ( 'title', $recepti->title. ' - Rice Kakis | Asian Store' )
@section ( 'description', $recepti->meta_description )
    @push('meta_tags')
        <link rel="canonical" href="{{ route('catalog.route.recepti', ['recepti' => $recepti]) }}" />
        <meta property="og:locale" content="hr_HR" />
        <meta property="og:type" content="product" />
        <meta property="og:title" content="{{ $recepti->title }}" />
        <meta property="og:description" content="{{ $recepti->translation->meta_description  }}" />
        <meta property="og:url" content="{{ route('catalog.route.recepti', ['recepti' => $recepti]) }}/{{ $recepti->translation->slug }}"  />
        <meta property="og:site_name" content="Rice Kakis | Asian Store" />
        <meta property="og:updated_time" content="{{ $recepti->updated_at  }}" />
        <meta property="og:image" content="{{ asset($recepti->image) }}" />
        <meta property="og:image:secure_url" content="{{ asset($recepti->image) }}" />
        <meta property="og:image:width" content="640" />
        <meta property="og:image:height" content="480" />
        <meta property="og:image:type" content="image/jpeg" />
        <meta property="og:image:alt" content="{{ asset($recepti->image) }}" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="{{ $recepti->title }}" />
        <meta name="twitter:description" content="{{ $recepti->translation->meta_description }}" />
        <meta name="twitter:image" content="{{ asset($recepti->image) }}" />
    @endpush
@endif
@section('content')
    <nav class="mb-4" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>{{ __('front/ricekakis.homepage') }}</a></li>
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('catalog.route.recepti') }}"><i class="ci-home"></i>{{ __('front/ricekakis.recepti') }}</a></li>
        </ol>
    </nav>
    <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
        @if(isset($receptis))
            <h1 class="h2 mb-3 mb-md-0 me-3">{{ __('front/ricekakis.recepti') }}</h1>
        @else
            <h1 class="h2 mb-3 mb-md-0 me-3">{{ $recepti->title }}</h1>
        @endif

    </section>



    @if(isset($receptis))

    <div class=" pb-5 mb-2 mb-md-4">


            <!-- Entries grid-->
            <div class="masonry-grid" data-columns="3" >
                @foreach ($receptis as $recepti)

                <article class="masonry-grid-item">
                    <div class="card">
                        <a class="blog-entry-thumb" href="{{ route('catalog.route.recepti', ['recepti' => $recepti]) }}/{{ $recepti->translation->slug }}"><span class="blog-entry-meta-label fs-sm"><i class="ci-pot"></i></span><img class="card-img-top" src="{{ $recepti->image }}" alt="{{ $recepti->translation->title }}"></a>
                        <div class="card-body">
                            <h2 class="h6 blog-entry-title"><a href="{{ route('catalog.route.recepti', ['recepti' => $recepti]) }}/{{ $recepti->translation->slug }}">{{ $recepti->translation->title }}</a></h2>
                            <p class="fs-sm">{{ $recepti->translation->short_description }}</p>
                        </div>

                    </div>
                </article>

                @endforeach

            </div>



    </div>
    @else
        <div class="mt-2 mb-5 fs-md" style="max-width:1240px">
                    <!-- Post meta-->
                    <!-- Gallery-->
                    <div class=" row pb-2">
                        <div class="col-sm-12 mb-2"><img src="{{ asset($recepti->image) }}" alt="{{ $recepti->translation->title }}"></div>

                    </div>
                    <!-- Post content-->

                    {!! $recepti->description !!}

        </div>


    @endif

@endsection
