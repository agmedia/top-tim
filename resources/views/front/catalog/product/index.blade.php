@extends('front.layouts.app')
@section ('title', $seo['title'])
@section ('description', $seo['description'])
@push('meta_tags')

    <link rel="canonical" href="{{ url($prod->url) }}" />
    <meta property="og:locale" content="hr_HR" />
    <meta property="og:type" content="product" />
    <meta property="og:title" content="{{ $seo['title'] }}" />
    <meta property="og:description" content="{{ $seo['description']  }}" />
    <meta property="og:url" content="{{ url($prod->url) }}"  />
    <meta property="og:site_name" content="Plava Krava" />
    <meta property="og:updated_time" content="{{ $prod->updated_at  }}" />
    <meta property="og:image" content="{{ asset($prod->image) }}" />
    <meta property="og:image:secure_url" content="{{ asset($prod->image) }}" />
    <meta property="og:image:width" content="640" />
    <meta property="og:image:height" content="480" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:image:alt" content="{{ $prod->image_alt }}" />
    <meta property="product:price:amount" content="{{ number_format($prod->price, 2) }}" />
    <meta property="product:price:currency" content="EUR" />
    <meta property="product:availability" content="instock" />
    <meta property="product:retailer_item_id" content="{{ $prod->sku }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seo['title'] }}" />
    <meta name="twitter:description" content="{{ $seo['description'] }}" />
    <meta name="twitter:image" content="{{ asset($prod->image) }}" />

@endpush

@if (isset($gdl))
    @section('google_data_layer')
        <script>
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({
                'event': 'view_item',
                'ecommerce': {
                    'items': [<?php echo json_encode($gdl); ?>]
                } });
        </script>
    @endsection
@endif

@section('content')
    <section class="mb-3">
        <div class="d-flex row justify-content-between">

            <div class="col-md-12">
                <div class="alert alert-info d-flex  mb-1 " role="alert">
                    <div class="alert-icon">
                        <i class="ci-gift"></i>
                    </div>
                    <small>Besplatna dostava za narudžbe iznad 50€</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Page title + breadcrumb-->
    <nav class="mb-4" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>Naslovnica</a></li>
            @if ($group)
                @if ($group && ! $cat && ! $subcat)
                  <!--  <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ \Illuminate\Support\Str::ucfirst($group) }}</li> -->
                @elseif ($group && $cat)
              <!--      <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group]) }}">{{ \Illuminate\Support\Str::ucfirst($group) }}</a></li> -->
                @endif

                @if ($cat && ! $subcat)
                    @if ($prod)
                        <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                    @else
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->title }}</li>
                    @endif
                @elseif ($cat && $subcat)
                    <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat]) }}">{{ $cat->title }}</a></li>
                    @if ($prod)
                        @if ($cat && ! $subcat)
                            <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat]) }}">{{ \Illuminate\Support\Str::limit($prod->name, 50) }}</a></li>
                        @else
                            <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat, 'subcat' => $subcat]) }}">{{ $subcat->title }}</a></li>
                        @endif
                    @endif
                @endif
            @endif

        </ol>
    </nav>
    <!-- Content-->
    <section class="row g-0 mx-n2 ">
        @include('back.layouts.partials.session')
        <!-- Product Gallery + description-->
        <div class="col-xl-6 px-2 mb-3">
            <div class="h-100 bg-light rounded-3 p-4">
                <div class="product-gallery">
                    <div class="product-gallery-preview order-sm-2">
                            @if ( ! empty($prod->image))
                                <div class="product-gallery-preview-item active" id="first"><img  src="{{ asset($prod->image) }}"  alt="{{ $prod->name }}" height="800"></div>
                            @endif
                            @if ($prod->images->count())
                                @foreach ($prod->images as $key => $image)
                                    <div class="product-gallery-preview-item" id="key{{ $key + 1 }}"><img  src="{{ asset($image->image) }}" alt="{{ $image->alt }}"  height="800"></div>
                                @endforeach
                            @endif
                    </div>
                    <div class="product-gallery-thumblist order-sm-1">
                        @if ($prod->images->count())
                            @if ( ! empty($prod->thumb))
                                <a class="product-gallery-thumblist-item active" href="#first"><img src="{{ asset($prod->thumb) }}" alt="{{ $prod->name }}"></a>
                            @endif
                            @foreach ($prod->images as $key => $image)
                                <a class="product-gallery-thumblist-item" href="#key{{ $key + 1 }}"><img src="{{ url('cache/thumb?size=100x100&src=' . $image->thumb) }}" width="100" height="100" alt="{{ $image->alt }}"></a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 px-2 mb-3">
            <div class="h-100 bg-light rounded-3 py-5 px-4 px-sm-5">

        @if ( $prod->quantity < 1)
                    <span class="badge bg-warning ">Rasprodano</span>
       @endif

   @if ($prod->main_price > $prod->main_special)
       <span class="badge bg-primary ">-{{ number_format(floatval(\App\Helpers\Helper::calculateDiscount($prod->price, $prod->special())), 0) }}%</span>
   @endif

   <div class="d-flex justify-content-between align-items-center mb-2">
       <a href="#reviews" id="openReview" data-scroll>
           <div class="star-rating">

               @for ($i = 0; $i < 5; $i++)
                   @if (floor($reviews->avg('stars')) - $i >= 1)
                       {{--Full Start--}}
                       <i class="star-rating-icon ci-star-filled active"></i>
                   @elseif ($reviews->avg('stars') - $i > 0)
                       {{--Half Start--}}
                       <i class="star-rating-icon ci-star"></i>
                   @else
                       {{--Empty Start--}}
                       <i class="star-rating-icon ci-star"></i>
                   @endif
               @endfor


           </div>
           <span class="d-inline-block fs-sm text-body align-middle mt-1 ms-1">{{ $reviews->count() }} @if($reviews->count() > 1) Recenzije @else Recenzija @endif</span>
       </a>
   </div>

   <h1 class="h3">{{ $prod->name }}</h1>

       <div class="mb-1">
           @if ($prod->main_price > $prod->main_special)
               <span class="h3 fw-normal text-accent me-1">{{ $prod->main_special_text }}</span>
               <span class="text-muted fs-lg me-3">*{{ $prod->main_price_text }}</span>

           @else
               <span class="h3 fw-normal text-accent me-1">{{ $prod->main_price_text }}</span>
           @endif

       </div>

   @if($prod->secondary_price_text)
       <div class="mb-1 mt-1 text-start">
           @if ($prod->main_price > $prod->main_special)
               <span class=" fs-sm text-muted me-1"> {{ $prod->secondary_special_text }}</span>
               <span class="text-muted fs-sm me-3">*{{ $prod->secondary_price_text }}</span>
           @else
               <span class="fs-sm text-muted  me-1">{{ $prod->secondary_price_text }}</span>
           @endif
       </div>
   @endif
   @if ($prod->main_price > $prod->main_special)

       <div class="mb-3 mt-1 text-start">
           <span class=" fs-sm text-muted me-1"> *Najniža cijena u zadnjih 30 dana.</span>
       </div>

   @endif
            @if ( $prod->quantity > 0)
   <add-to-cart-btn id="{{ $prod->id }}" available="{{ $prod->quantity }}"></add-to-cart-btn>
            @endif
   <!-- Product panels-->
   <div class="accordion mb-4" id="productPanels">
       <div class="accordion-item">
           <h3 class="accordion-header"><a class="accordion-button" href="#productInfo" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="productInfo"><i class="ci-announcement text-muted fs-lg align-middle mt-n1 me-2"></i>Osnovne informacije</a></h3>
           <div class="accordion-collapse collapse show" id="productInfo" data-bs-parent="#productPanels">
               <div class="accordion-body">

                   <ul class="fs-sm ps-4 mb-0">
                       @if ($prod->author)
                           <li><strong>Autor:</strong> <a href="{{ route('catalog.route.author', ['author' => $prod->author]) }}">{{ $prod->author->title }} </a></li>
                       @endif
                       @if ($prod->publisher)
                           <li><strong>Nakladnik:</strong> <a href="{{ route('catalog.route.publisher', ['publisher' => $prod->publisher]) }}">{{ $prod->publisher->title }}</a> </li>
                       @endif
                       @if ($prod->isbn)
                       <li><strong>EAN:</strong> {{ $prod->isbn }} </li>
                       @endif
                           @if ($prod->quantity)
                               @if ($prod->decrease)
                                   <li><strong>Dostupnost:</strong> {{ $prod->quantity }} </li>
                               @else
                                   <li><strong>Dostupnost:</strong> Dostupno</li>
                               @endif
                           @else
                               <li><strong>Dostupnost:</strong> Rasprodano</li>
                           @endif

                           <li><strong>Stanje:</strong> Nova knjiga</li>
                   </ul>

               </div>
           </div>
       </div>
       <div class="accordion-item">
           <h3 class="accordion-header"><a class="accordion-button collapsed" href="#shippingOptions" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="shippingOptions"><i class="ci-delivery text-muted lead align-middle mt-n1 me-2"></i>Opcije dostave</a></h3>
           <div class="accordion-collapse collapse" id="shippingOptions" data-bs-parent="#productPanels">
               <div class="accordion-body fs-sm">

                   @foreach($shipping_methods as $shipping_method)
                       <div class="d-flex justify-content-between ">
                           <div>
                               <div class="fw-semibold text-dark">{{ $shipping_method->title }}</div>
                               <div class="fs-sm text-muted"> Besplatna dostava za narudžbe iznad {{ config('settings.free_shipping') }}€</div>
                               @if ($prod->shipping_time)

                                       <span class=" fs-sm text-muted me-1"> Rok dostave: {{ $prod->shipping_time }}</span>

                               @endif
                           </div>
                           <div>{{ $shipping_method->data->price }}€ <small> {{ number_format(config('settings.hrk_divide_amount') * $shipping_method->data->price), 2 }}kn</small></div>
                       </div>
                   @endforeach

               </div>
               <small class="mt-2"></small>
           </div>
       </div>
       <div class="accordion-item">
           <h3 class="accordion-header"><a class="accordion-button collapsed" href="#localStore" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="localStore"><i class="ci-card text-muted fs-lg align-middle mt-n1 me-2"></i>Načini plaćanja</a></h3>
           <div class="accordion-collapse collapse" id="localStore" data-bs-parent="#productPanels">
               <div class="accordion-body fs-sm">


                   @foreach($payment_methods as $payment_method)
                       @if($prod->origin == 'Engleski' and $payment_method->code == 'cod' )

                       @else
                       <div class="d-flex justify-content-between border-bottom py-2">
                           <div>
                               <div class="fw-semibold text-dark">{{ $payment_method->title }}</div>
                               @if (isset($payment_method->data->description))
                                   <div class="fs-sm text-muted">{{ $payment_method->data->description }}</div>
                               @endif
                           </div>
                       </div>
                       @endif
                   @endforeach

               </div>


           </div>
       </div>
   </div>
   <!-- Sharing-->
   <!-- ShareThis BEGIN --><div class="sharethis-inline-share-buttons"></div><!-- ShareThis END -->
</div>
</div>
</section>
<!-- Related products-->

<section class="mx-n2 pb-4 px-2 mb-xl-3" id="tabs_widget">
<div class="bg-light px-2 mb-3 shadow-lg rounded-3">
<!-- Tabs-->
<ul class="nav nav-tabs" role="tablist">
   <li class="nav-item"><a class="nav-link py-4 px-sm-4 active" href="#specs" data-bs-toggle="tab" role="tab"><span>Opis</span> </a></li>
   <li class="nav-item"><a class="nav-link py-4 px-sm-4" href="#reviews" data-bs-toggle="tab" role="tab">Recenzije <span class="fs-sm opacity-60">({{ $reviews->count() }})</span></a></li>
</ul>
<div class="px-4 pt-lg-3 pb-3 mb-5">
   <div class="tab-content px-lg-3">
       <!-- Tech specs tab-->
       <div class="tab-pane fade show active" id="specs" role="tabpanel">
           <!-- Specs table-->
           <div class="row pt-2">
               <div class="col-lg-7 col-sm-7">
                   <h3 class="h6">Sažetak</h3>
                   <div class=" fs-md pb-2 mb-4">
                       {!! $prod->description !!}
                   </div>


                   @if ($prod->author_web_url or $prod->serial_web_url or $prod->wiki_url or $prod->youtube_channel or $prod->youtube_product_url or $prod->goodreads_author_url or $prod->goodreads_book_url)

                       <h3 class="h6 mt-4">Multimedia i linkovi</h3>
                       <ul class="list-unstyled fs-sm pb-2">
                           @if ($prod->youtube_product_url)
                               <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">YouTube Video:</span><span><i class="ci-youtube text-muted fs-lg align-middle mt-n1 me-1"></i> <a href="{{ $prod->youtube_product_url }}">Pogledajte video</a></span></li>
                           @endif

                           @if ($prod->youtube_channel)
                                <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">YouTube Kanal:</span><span><i class="ci-youtube text-muted fs-lg align-middle mt-n1 me-1"></i> <a href="{{ $prod->youtube_channel }}">Pogledajte video</a></span></li>
                           @endif

                           @if ($prod->wiki_url)
                                   <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Wikipedia:</span><span><i class="ci-link text-muted fs-lg align-middle mt-n1 me-1"></i> <a href="{{ $prod->wiki_url }}">Pogledajte stranicu</a></span></li>
                           @endif

                           @if ($prod->author_web_url)
                                   <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Web stranica autora:</span><span><i class="ci-link text-muted fs-lg align-middle mt-n1 me-1"></i> <a href="{{ $prod->author_web_url }}">Pogledajte stranicu</a></span></li>
                           @endif

                            @if ($prod->serial_web_url)
                                   <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Web stranica serijala:</span><span><i class="ci-link text-muted fs-lg align-middle mt-n1 me-1"></i> <a href="{{ $prod->serial_web_url }}">Pogledajte stranicu</a></span></li>
                            @endif

                            @if ($prod->goodreads_author_url)
                                   <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Goodreads stranica autora:</span><span><i class="ci-link text-muted fs-lg align-middle mt-n1 me-1"></i> <a href="{{ $prod->goodreads_author_url }}">Pogledajte stranicu</a></span></li>

                            @endif

                            @if ($prod->goodreads_book_url)
                                   <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Goodreads stranica knjige:</span><span><i class="ci-link text-muted fs-lg align-middle mt-n1 me-1"></i> <a href="{{ $prod->goodreads_book_url }}">Pogledajte stranicu</a></span></li>

                            @endif
                       </ul>

                   @endif
               </div>
               <div class="col-lg-5 col-sm-5 ">
                   <h3 class="h6">Dodatne informacije</h3>
                   <ul class="list-unstyled fs-sm pb-2">


                       @if ($prod->author)
                               <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">Autor:</span><span><a href="{{ route('catalog.route.author', ['author' => $prod->author]) }}">{{ Illuminate\Support\Str::limit($prod->author->title, 30) }}</a></span></li>
                       @endif
                       @if ($prod->publisher)
                               <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">Nakladnik:</span><span><a href="{{ route('catalog.route.publisher', ['publisher' => $prod->publisher]) }}">{{ Illuminate\Support\Str::limit($prod->publisher->title, 30) }}</a> </span></li>
                       @endif


                       @if ($prod->binding)
                           <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">Uvez:</span><span>{{ $prod->binding  }}</span></li>
                        @endif
                       @if ($prod->origin)
                           <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">Jezik:</span><span>{{ $prod->origin  }}</span></li>
                       @endif
                        @if ($prod->year)
                           <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">Godina izdanja:</span><span>{{ $prod->year }}</span></li>
                       @endif
                       @if ($prod->pages)
                           <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">Broj stranica:</span><span>{{ $prod->pages }}</span></li>
                      @endif
                       @if ($prod->dimensions)
                           <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">Dimenzije:</span><span>{{ $prod->dimensions.' cm'  }}</span></li>
                       @endif
                       @if ($prod->isbn)
                            <li class="d-flex justify-content-between pb-2 border-bottom"><span class="text-muted">EAN:</span><span>{{ $prod->isbn }}</span></li>
                       @endif
                   </ul>

               </div>
           </div>
       </div>
       <!-- Reviews tab-->
       <div class="tab-pane fade" id="reviews" role="tabpanel">
           <!-- Reviews-->
           <div class="row pt-2 pb-3">
               <div class="col-lg-4 col-md-5 mb-3">
                   <h2 class="h3 mb-1"> {{ $reviews->count() }} @if($reviews->count() > 1) Recenzije @else Recenzije @endif</h2>
                   <div class="star-rating me-2">

                       @for ($i = 0; $i < 5; $i++)
                           @if (floor($reviews->avg('stars')) - $i >= 1)
                               {{--Full Start--}}
                               <i class="ci-star-filled fs-sm text-accent me-1"></i>
                           @elseif ($reviews->avg('stars') - $i > 0)
                               {{--Half Start--}}
                               <i class="ci-star fs-sm text-muted me-1"></i>
                           @else
                               {{--Empty Start--}}
                               <i class="ci-star fs-sm text-muted me-1"></i>
                           @endif
                       @endfor

                   </div><span class="d-inline-block align-middle">{{ number_format($reviews->avg('stars'), 2) }} Prosječna ocjena</span>

               </div>
               <div class="col-lg-8 col-md-7">
                   @for ($i = 5; $i > 0; $i--)
                       <div class="d-flex align-items-center mb-2">
                           <div class="text-nowrap me-3"><span class="d-inline-block align-middle text-muted">{{ $i }}</span><i class="ci-star-filled fs-xs ms-1"></i></div>
                           <div class="w-100">
                               <div class="progress" style="height: 4px;">
                                   <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $prod->percentreviews($reviews->where('stars', $i)->count(), $reviews->count()) }}%;" aria-valuenow="{{ $prod->percentreviews($reviews->where('stars', $i)->count(), $reviews->count()) }}" aria-valuemin="0" aria-valuemax="100"></div>
                               </div>
                           </div><span class="text-muted ms-3">{{ $reviews->where('stars', $i)->count() }}</span>
                       </div>
                   @endfor
               </div>
           </div>
           <hr class="mt-4 mb-3">
           <div class="row py-4">
               <!-- Reviews list-->
               <div class="col-md-7">

                   @if($reviews->count())

                       @foreach($reviews as $review)
                           <!-- Review-->
                           <div class="product-review pb-4 mb-4 border-bottom">
                               <div class="d-flex mb-3">
                                   <div class="d-flex align-items-center me-4 pe-2">
                                       <div>
                                           <h6 class="fs-sm mb-0">{{ $review->fname }} {{ $review->lname }}<span class="fs-ms fw-light text-muted ml-3"> {{ \Carbon\Carbon::make($review->created_at)->locale('hr')->format('d.m.Y.') }}</span></h6>
                                       </div>
                                   </div>
                                   <div>
                                       <div class="star-rating" style="vertical-align: top !important;">
                                           @for ($i = 0; $i < 5; $i++)
                                               @if (floor($review->stars) - $i >= 1)
                                                   {{--Full Start--}}
                                                   <i class="star-rating-icon ci-star-filled active"></i>
                                               @elseif ($review->stars - $i > 0)
                                                   {{--Half Start--}}
                                                   <i class="star-rating-icon ci-star"></i>
                                               @else
                                                   {{--Empty Start--}}
                                                   <i class="star-rating-icon ci-star"></i>
                                               @endif
                                           @endfor
                                       </div>

                                   </div>
                               </div>
                               <p class="fs-md mb-2">{{ strip_tags($review->message) }}</p>
                           </div>

                       @endforeach
                   @else
                       <p>Trenutno nema ocjena i komentara za ovaj artikl!</p>
                   @endif

               </div>
               <!-- Leave review form-->
               <div class="col-md-5 mt-2 pt-4 mt-md-0 pt-md-0">
                   <div class="bg-secondary py-grid-gutter px-grid-gutter rounded-3">
                       <h3 class="h4 pb-2">Napišite recenziju</h3>
                       <form class="needs-validation" method="post" action="{{ route('komentar.proizvoda') }}" novalidate>
                           @csrf
                           <div class="mb-3">
                               <label class="form-label" for="review-name">Vaše Ime<span class="text-danger">*</span></label>
                               <input class="form-control" type="text" required id="review-name" name="name">
                               @error('name')
                               <div class="fs-md fw-light text-danger">Molimo unesite vaše ime!</div>
                               @enderror
                               <div class="invalid-feedback">Molimo unesite vaše ime!</div>
                           </div>
                           <div class="mb-3">
                               <label class="form-label" for="review-email">Vaš email<span class="text-danger">*</span></label>
                               <input class="form-control" type="email" required id="review-email" name="email">
                               @error('email')
                               <div class="fs-md fw-light text-danger">Molimo upišite ispravnu email adresu!</div>
                               @enderror
                               <div class="invalid-feedback">Molimo upišite ispravnu email adresu!</div>
                           </div>
                           <div class="mb-3">
                               <label class="form-label" for="review-rating">Ocjena<span class="text-danger">*</span></label>
                               <select class="form-select" required id="review-stars" name="stars">
                                   <option value="">Odaberite ocjenu</option>
                                   <option value="5">5 stars</option>
                                   <option value="4">4 stars</option>
                                   <option value="3">3 stars</option>
                                   <option value="2">2 stars</option>
                                   <option value="1">1 star</option>
                               </select>
                               @error('stars')
                               <div class="fs-md fw-light text-danger">Molimo odaberite ocjenu!</div>
                               @enderror
                               <div class="invalid-feedback">Molimo odaberite ocjenu!</div>
                           </div>
                           <div class="mb-3">
                               <label class="form-label" for="review-text">Rocenzija<span class="text-danger">*</span></label>
                               <textarea class="form-control" rows="6" required id="review-message" name="message"></textarea>
                               @error('message')
                               <div class="fs-md fw-light text-danger">Molimo napišite recenziju!</div>
                               @enderror
                               <div class="invalid-feedback">Molimo napišite recenziju!</div>
                           </div>

                           <input type="hidden" name="product_id" value="{{ $prod->id }}">
                           <button class="btn btn-primary btn-shadow d-block w-100" type="submit">Pošalji</button>
                       </form>
                   </div>
               </div>
           </div>
       </div>
   </div>
</div>
</div>
</section>
<!-- Product description-->
<section class="pb-5 mb-2 mb-xl-4">
<div class=" flex-wrap justify-content-between align-items-center  text-center">
<h2 class="h3 mb-3 pt-1 font-title me-3 text-center"> POVEZANI PROIZVODI</h2>

</div>
<div class="tns-carousel tns-controls-static tns-controls-outside tns-nav-enabled pt-2">
<div class="tns-carousel-inner" data-carousel-options="{&quot;items&quot;: 2, &quot;gutter&quot;: 16, &quot;controls&quot;: true, &quot;autoHeight&quot;: true, &quot;responsive&quot;: {&quot;0&quot;:{&quot;items&quot;:2}, &quot;480&quot;:{&quot;items&quot;:2}, &quot;720&quot;:{&quot;items&quot;:3}, &quot;991&quot;:{&quot;items&quot;:2}, &quot;1140&quot;:{&quot;items&quot;:3}, &quot;1300&quot;:{&quot;items&quot;:4}, &quot;1500&quot;:{&quot;items&quot;:5}}}">
   @foreach ($related as $cat_product)
       @if ($cat_product->id != $prod->id)
           <div>
               @include('front.catalog.category.product', ['product' => $cat_product])
           </div>
       @endif
   @endforeach
</div>
</div>
</section>

@endsection

@push('js_after')
<script type="application/ld+json">
{!! collect($crumbs)->toJson() !!}
</script>
<script type="application/ld+json">
{!! collect($bookscheme)->toJson() !!}
</script>
<script type='text/javascript' src='https://platform-api.sharethis.com/js/sharethis.js#property=6134a372eae16400120a5035&product=sop' async='async'></script>
<script>
$('#openReview').on('click', function() {
$('.nav-tabs a[href="#reviews"]').tab('show');
//  document.getElementById("tabs_widget").scrollIntoView();
});
</script>
@endpush
