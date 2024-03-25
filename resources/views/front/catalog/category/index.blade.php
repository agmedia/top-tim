@extends('front.layouts.app')



@if (isset($group) && $group)
    @if ($group && ! $cat && ! $subcat)
        @section ( 'title',  \Illuminate\Support\Str::ucfirst($group). ' - Rice Kakis | Asian Store' )
    @endif
    @if ($cat && ! $subcat)
        @section ( 'title',  $cat->translation->title . ' - Rice Kakis | Asian Store' )
        @section ( 'description', $cat->translation->meta_description )

        @push('meta_tags')
            <link rel="canonical" href="{{ env('APP_URL')}}kategorija-proizvoda/{{ $cat->translation['slug'] }}" />
        @endpush


    @elseif ($cat && $subcat)


        @section ( 'title', $subcat->translation->meta_title . ' - Rice Kakis | Asian Store' )
        @section ( 'description',  $subcat->translation->meta_description )

        @push('meta_tags')
            <link rel="canonical" href="{{ env('APP_URL')}}kategorija-proizvoda/{{ $subcat->translation['slug'] }}" />
        @endpush

    @endif
@endif

@if (isset($author) && $author)
    @section ('title',  $seo->translation['title'])
    @section ('description', $seo->translation['description'])


    @push('meta_tags')
        <link rel="canonical" href="{{ env('APP_URL')}}{{ $author->translation['url'] }}" />
    @endpush

@endif

@if (isset($publisher) && $publisher)
    @section ('title',  $seo->translation['title'])
    @section ('description', $seo->translation['description'])
    @push('meta_tags')
        <link rel="canonical" href="{{ env('APP_URL')}}{{ $publisher->translation['url'] }}" />
    @endpush
@endif


@if (isset($brand) && $brand)


    @section ('title',  $brand->translation->title)
    @section ('description', $brand->translation->descriptiom)
    @push('meta_tags')
        <link rel="canonical" href="{{ env('APP_URL')}}{{ $brand->translation->slug }}" />
    @endpush
@endif

@if (isset($meta_tags))
    @push('meta_tags')

        @foreach ($meta_tags as $tag)
            <meta name={{ $tag->translation['name'] }} content={{ $tag->translation['content'] }}>
        @endforeach
    @endpush
@endif


@section('content')



    @if (Route::currentRouteName() == 'pretrazi')
        <section class="d-md-flex justify-content-between align-items-center mb-2 pb-2">
            <h1 class="h2 mb-2 mb-md-0 me-3"><span class="small fw-light me-2">{{ __('front/ricekakis.rezultati') }}:</span> {{ request()->input('pojam') }}</h1>
        </section>
    @endif

    @if (isset($brand) && $brand)

        <nav class="mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>{{ __('front/ricekakis.homepage') }}</a></li>
                <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route.author') }}">Autori</a></li>
                @if ( ! $cat && ! $subcat)
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $brand->title }}</li>
                @endif
                @if ($cat && ! $subcat)
                    <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route.abrand', ['brand' => $brand]) }}">{{ $brand->title }}</a></li>
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->title }}</li>
                @elseif ($cat && $subcat)
                    <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route.brand', ['brand' => $brand]) }}">{{ $brand->title }}</a></li>
                    <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route.brand', ['brand' => $brand, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $subcat->title }}</li>
                @endif
            </ol>
        </nav>

        <section class="d-md-flex justify-content-between align-items-center mb-2 pb-2">
            <h1 class="h2 mb-2 mb-md-0 me-3">{{ $brand->title }}</h1>
        </section>
    @endif



    @if (isset($group) && $group)

        <nav class="mb-2" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>{{ __('front/ricekakis.homepage') }}</a></li>
                @if ($group && ! $cat && ! $subcat)
                    <!-- <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ \Illuminate\Support\Str::ucfirst($group) }}</li> -->
                @elseif ($group && $cat)
                    <!--    <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group]) }}">{{ \Illuminate\Support\Str::ucfirst($group) }}</a></li>-->
                @endif
                @if ($cat && ! $subcat)
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->translation->title }}</li>
                @elseif ($cat && $subcat)
                    <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat->translation->slug]) }}">{{ $cat->translation->title }}</a></li>
                    <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $subcat->translation->title }}</li>
                @endif
            </ol>
        </nav>


        <section class="py-2 mb-1">

            @if ($group && ! $cat && ! $subcat)


                <h1 class="h2 mb-4  me-3">{{ __('front/ricekakis.web_shop') }}</h1>
                <div class="row">

                    @foreach ($list as $item)
                        <!-- Product-->
                        <div class="article col-md-3 mb-grid-gutter">
                            <a class="card border-0 shadow" href="{{ route('catalog.route', ['group' => $group]) }}/{{ $item['slug'] }}">
                                <img class="card-img-top p-3" loading="lazy" width="200" height="200" src="{{ $item['image'] }}" alt="Kategorija {{ $item['title'] }}">
                                <div class="card-body py-2 text-center px-0">
                                    <h3 class="h4 mt-1 font-title text-primary">{{ $item['title'] }}</h3>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>





            @endif
            @if ($cat && ! $subcat)
                <h1 class="h2 mb-2 mb-md-0 me-3">{{ $cat->translation->title }}</h1>
            @elseif ($cat && $subcat)
                <h1 class="h2 mb-2 mb-md-0 me-3">{{ $subcat->translation->title }}</h1>
            @endif


        </section>

        @if ($cat && ! $subcat)

            @if ($cat->subcategories()->count())
                <section class="py-2 mb-1">

                    <div class="tns-carousel">
                        <div class="tns-carousel-inner" data-carousel-options='{"items": 2, "controls": true, "autoHeight": false, "responsive": {"0":{"items":2, "gutter": 10},"480":{"items":2, "gutter": 10},"800":{"items":4, "gutter": 20}, "1300":{"items":5, "gutter": 30}, "1800":{"items":6, "gutter": 30}}}'>
                            @foreach ($cat->subcategories as $item)
                                <!-- Product-->
                                <div class="article mb-grid-gutter">
                                    <a class="card border-0 shadow" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat->translation->slug, 'subcat' => $item->translation->slug]) }}">
                                        <img class="card-img-top p-3" loading="lazy" width="200" height="200" src="{{ $item['image'] }}" alt="Kategorija {{ $item->translation->title }}">
                                        <div class="card-body py-2 text-center px-0">
                                            <h3 class="h4 mt-1 font-title text-primary">{{ $item->translation->title }}</h3>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>




                </section>

            @endif

        @endif

    @endif


    <products-view ids="{{ isset($ids) ? $ids : null }}"
                   group="{{ isset($group) ? $group : null }}"
                   cat="{{ isset($cat) ? $cat['id'] : null }}"
                   subcat="{{ isset($subcat) ? $subcat['id'] : null }}"
                   author="{{ isset($author) ? $author['slug'] : null }}"
                   brand="{{ isset($brand) ? $brand->translation->slug : null }}"
                   publisher="{{ isset($publisher) ? $publisher['slug'] : null }}">
    </products-view>








    @if (isset($author) && $author && ! empty($author->description))
        <section class="col">
            <div class="card p2-5 border-0 mt-5 shadow mb-5" >
                <div class="card-body py-md-4 py-3 px-4 ">
                    <h2 class="fs-5 mb-4 mt-2">{{ $author->meta_title }}</h2>

                    {!!$author->description !!}
                </div>
            </div>
        </section>

    @endif


    @if (isset($brand) && $brand && ! empty($brand->description))
        <section class="col">
            <div class="card p2-5 border-0 mt-5 shadow mb-5" >
                <div class="card-body py-md-4 py-3 px-4 ">
                    <h2 class="fs-5 mb-4 mt-2">{{ $brand->translation->meta_title }}</h2>

                    {!!$brand->translation->description !!}
                </div>
            </div>
        </section>

    @endif



    @if ($cat && !$subcat)
        <section class="col">
            <div class="card p2-5 border-0 mt-5 shadow mb-5" >
                <div class="card-body py-md-4 py-3 px-4 ">
                    <h2 class="fs-5 mb-4 mt-2">{{ $cat->translation->meta_title }}</h2>
                    {!! $cat->translation->description !!}
                </div>
            </div>
        </section>
    @elseif ($subcat)
        <section class="col">
            <div class="card p2-5 border-0 mt-5 shadow mb-5" >
                <div class="card-body py-md-4 py-3 px-4 ">
                    <h2 class="fs-5 mb-4 mt-2">{{ $subcat->translation->meta_title }}</h2>
                    {!! $subcat->translation->description !!}
                </div>
            </div>
        </section>
    @endif







@endsection

@push('js_after')
    <script type="application/ld+json">
        {!! collect($crumbs)->toJson() !!}
    </script>
@endpush

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
