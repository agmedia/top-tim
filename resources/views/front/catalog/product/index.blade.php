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
    <meta property="og:site_name" content="Rice Kakis | Asian Store" />
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
{{--
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
@endif--}}

@section('content')


    <!-- Page title + breadcrumb-->
    <nav class="mb-4" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>{{ __('front/ricekakis.homepage') }}</a></li>
            @if ($group)
                @if ($group && ! $cat && ! $subcat)
                  <!--  <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ \Illuminate\Support\Str::ucfirst($group) }}</li> -->
                @elseif ($group && $cat)
              <!--      <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group]) }}">{{ \Illuminate\Support\Str::ucfirst($group) }}</a></li> -->
                @endif

                @if ($cat && ! $subcat)
                    @if ($prod)
                        <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat->translation->slug]) }}">{{ $cat->translation->title }}</a></li>
                    @else
                        <li class="breadcrumb-item text-nowrap active" aria-current="page">{{ $cat->translation->title }}</li>
                    @endif
                @elseif ($cat && $subcat)
                    <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat->translation->slug]) }}">{{ $cat->translation->title }}</a></li>
                    @if ($prod)
                        @if ($cat && ! $subcat)
                            <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat->translation->slug]) }}">{{ \Illuminate\Support\Str::limit($prod->translation->name, 50) }}</a></li>
                        @else
                            <li class="breadcrumb-item text-nowrap active" aria-current="page"><a class="text-nowrap" href="{{ route('catalog.route', ['group' => $group, 'cat' => $cat->translation->slug, 'subcat' => $subcat->translation->slug]) }}">{{ $subcat->translation->title }}</a></li>
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
            <div class="h-100 bg-light rounded-3 p-4 position-relative">
                <div class="btn-wishlist-block">

                    @if($prod->vegan)
                        <button class="btn-wishlist  btn-lg" type="button" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Vegan" data-bs-original-title="Vegan"><img src="{{ asset('image/vegan.svg') }}" alt="Vegan" width="35" height="35" style="width:31px"/></button>
                    @endif
                    @if($prod->vegetarian)
                        <button class="btn-wishlist btn-lg" type="button" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Vegeterian" data-bs-original-title="Vegeterian"><img src="{{ asset('image/vegeterian.svg') }}" alt="Vegeterian"  width="35" height="35" style="width:31px" /></button>
                    @endif
                    @if($prod->glutenfree)
                        <button class="btn-wishlist btn-lg" type="button" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Gluten Free" data-bs-original-title="Gluten Free"><img src="{{ asset('image/gluten-free.svg') }}" alt="Gluten Free"  width="35" height="35" style="width:35px" /></button>
                    @endif
                </div>

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
                        @if ($prod->images->count() > 1)
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
                    <span class="badge bg-warning ">{{ __('front/ricekakis.rasprodano') }}</span>
       @endif

   @if ($prod->main_price > $prod->main_special)
       <span class="badge bg-primary ">-{{ number_format(floatval(\App\Helpers\Helper::calculateDiscount($prod->price, $prod->special())), 0) }}%</span>
   @endif

   <div class="d-flex justify-content-between align-items-center mb-2">
       <a id="openReview" href="#reviews" data-scroll>
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
           <span class="d-inline-block fs-sm text-body align-middle mt-1 ms-1">{{ $reviews->count() }} @if($reviews->count() > 1) {{ __('front/ricekakis.reviews') }} @else  {{ __('front/ricekakis.review') }} @endif</span>
       </a>
   </div>

   <h1 class="h3 fs-3 fw-medium">{{ $prod->name }}</h1>

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
           <span class=" fs-sm text-muted me-1">  {{ __('front/ricekakis.lowest_price') }}</span>
       </div>

   @endif
            @if ( $prod->quantity > 0)
   <add-to-cart-btn id="{{ $prod->id }}" available="{{ $prod->quantity }}"></add-to-cart-btn>
            @endif
   <!-- Product panels-->
   <div class="accordion mb-4" id="productPanels">
       <div class="accordion-item">
           <h3 class="accordion-header"><a class="accordion-button" href="#productInfo" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="productInfo"><i class="ci-announcement text-muted fs-lg align-middle mt-n1 me-2"></i> {{ __('front/ricekakis.osnovne_informacije') }}</a></h3>
           <div class="accordion-collapse collapse show" id="productInfo" data-bs-parent="#productPanels">
               <div class="accordion-body">

                   <ul class="fs-sm ps-4 mb-0">


                       @if ($prod->brand)
                           <li><strong>Brand:</strong> <a href="{{ route('catalog.route.brand', ['brand' => $prod->brand->slug]) }}">{{ $prod->brand->title}} </a></li>
                       @endif

                       @if ($prod->isbn)
                       <li><strong>EAN:</strong> {{ $prod->isbn }} </li>
                       @endif
                           @if ($prod->quantity)

                                   <li><strong>{{ __('front/ricekakis.dostupnost') }}:</strong> {{$prod->quantity}} </li>

                           @else
                               <li><strong>{{ __('front/ricekakis.dostupnost') }}:</strong>{{ __('front/ricekakis.rasprodano') }}</li>
                           @endif


                   </ul>

               </div>
           </div>
       </div>
       <div class="accordion-item">
           <h3 class="accordion-header"><a class="accordion-button collapsed" href="#shippingOptions" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="shippingOptions"><i class="ci-delivery text-muted lead align-middle mt-n1 me-2"></i>{{ __('front/ricekakis.opcije_dostave') }}</a></h3>
           <div class="accordion-collapse collapse" id="shippingOptions" data-bs-parent="#productPanels">
               <div class="accordion-body fs-sm">

                   @foreach($shipping_methods as $shipping_method)
                       <div class="d-flex justify-content-between ">
                           <div>

                               <div class="fw-semibold text-dark">{{$shipping_method->title->{ current_locale() } }}</div>
                               <div class="fs-sm text-muted"> {{ __('front/ricekakis.besplatna_dostava') }} {{ config('settings.free_shipping') }}€</div>
                               @if ($prod->shipping_time)

                                       <span class=" fs-sm text-muted me-1"> {{ __('front/ricekakis.rok_dostave') }}: {{ $prod->shipping_time }}</span>

                               @endif
                           </div>
                           <div>{{ $shipping_method->data->price }}€</div>
                       </div>
                   @endforeach

               </div>
               <small class="mt-2"></small>
           </div>
       </div>
       <div class="accordion-item">
           <h3 class="accordion-header"><a class="accordion-button collapsed" href="#localStore" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="localStore"><i class="ci-card text-muted fs-lg align-middle mt-n1 me-2"></i>{{ __('front/ricekakis.nacin_placanja') }}</a></h3>
           <div class="accordion-collapse collapse" id="localStore" data-bs-parent="#productPanels">
               <div class="accordion-body fs-sm">


                   @foreach($payment_methods as $payment_method)
                       <div class="d-flex justify-content-between border-bottom py-2">
                           <div>
                               <div class="fw-semibold text-dark">{{ $payment_method->title->{ current_locale() } }}</div>
                               @if (isset($payment_method->data->short_description))
                                   <div class="fs-sm text-muted">{{ $payment_method->data->short_description->{ current_locale() } }}</div>
                               @endif
                           </div>
                       </div>

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
   <li class="nav-item"><a class="nav-link py-4 px-sm-4 active" href="#specs" data-bs-toggle="tab" role="tab"><span>{{ __('front/ricekakis.description') }}</span> </a></li>
   <li class="nav-item"><a class="nav-link py-4 px-sm-4" href="#reviews" data-bs-toggle="tab" role="tab">{{ __('front/ricekakis.reviews') }} <span class="fs-sm opacity-60">({{ $reviews->count() }})</span></a></li>
</ul>
<div class="px-4 pt-lg-3 pb-3 mb-5">
   <div class="tab-content px-lg-3">
       <!-- Tech specs tab-->
       <div class="tab-pane fade show active" id="specs" role="tabpanel">
           <!-- Specs table-->
           <div class="row pt-2">
               <div class="col-lg-9 col-sm-12">
                   <h2 class="h6">{{ $prod->name  }}</h2>
                   <div class=" fs-md pb-2 mb-4">
                       {!! $prod->description !!}
                   </div>



               </div>
               <div class="col-lg-5 col-sm-5 ">

                   @if ($prod->sastojci or $prod->podaci)

                      <h3 class="h6">{{ __('front/ricekakis.dodatne_informacije') }}</h3>

                   <!-- Light table with striped rows -->

                   <!-- Dark table with striped rows -->
                       <div class="table-responsive">
                           <table class="table table-light table-striped fs-sm">

                               <tbody>

                               @if ($prod->sastojci)
                                   <tr>
                                       <th>{{ __('front/ricekakis.sastojci') }}</th>
                                   </tr>
                                   <tr>
                                         <td>{!! $prod->sastojci !!}</td>
                                   </tr>
                               @endif
                               @if ($prod->podaci)
                                   <tr>
                                       <th>{{ __('front/ricekakis.podaci_o_prehrani') }}</th>
                                   </tr>
                                   <tr>
                                        <td>{!! $prod->podaci !!}</td>
                                   </tr>
                               @endif

                               </tbody>
                           </table>
                       </div>
                   @endif

               </div>
           </div>
       </div>
       <!-- Reviews tab-->
       <div class="tab-pane fade" name="reviews" id="reviews" role="tabpanel">
           <!-- Reviews-->
           <div class="row pt-2 pb-3">
               <div class="col-lg-4 col-md-5 mb-3">
                   <h4 class="h3 mb-1"> {{ $reviews->count() }} @if($reviews->count() > 1) {{ __('front/ricekakis.reviews') }}  @else {{ __('front/ricekakis.review') }}  @endif</h4>
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

                   </div><span class="d-inline-block align-middle">{{ number_format($reviews->avg('stars'), 2) }} {{ __('front/ricekakis.prosjecna_ocjena') }}  </span>

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
                       <p>{{ __('front/ricekakis.trenutno_nema') }}</p>
                   @endif

               </div>
               <!-- Leave review form-->
               <div class="col-md-5 mt-2 pt-4 mt-md-0 pt-md-0">
                   <div class="bg-secondary py-grid-gutter px-grid-gutter rounded-3">
                       <h4 class="h4 pb-2">{{ __('front/ricekakis.napisite_recenziju') }} </h4>
                       <form class="needs-validation" method="post" action="{{ route('komentar.proizvoda') }}" novalidate>
                           @csrf
                           <div class="mb-3">
                               <label class="form-label" for="review-name">{{ __('front/ricekakis.vase_ime') }} <span class="text-danger">*</span></label>
                               <input class="form-control" type="text" required id="review-name" name="name">
                               @error('name')
                               <div class="fs-md fw-light text-danger">{{ __('front/ricekakis.vase_ime_error') }}</div>
                               @enderror
                               <div class="invalid-feedback">{{ __('front/ricekakis.vase_ime_error') }} </div>
                           </div>
                           <div class="mb-3">
                               <label class="form-label" for="review-email">{{ __('front/ricekakis.vase_email') }} <span class="text-danger">*</span></label>
                               <input class="form-control" type="email" required id="review-email" name="email">
                               @error('email')
                               <div class="fs-md fw-light text-danger">{{ __('front/ricekakis.vase_email_error') }} </div>
                               @enderror
                               <div class="invalid-feedback">{{ __('front/ricekakis.vase_email_error') }} </div>
                           </div>
                           <div class="mb-3">
                               <label class="form-label" for="review-rating">{{ __('front/ricekakis.review') }} <span class="text-danger">*</span></label>
                               <select class="form-select" required id="review-stars" name="stars">
                                   <option value="">{{ __('front/ricekakis.odaberite_ocjenu') }} </option>
                                   <option value="5">5 stars</option>
                                   <option value="4">4 stars</option>
                                   <option value="3">3 stars</option>
                                   <option value="2">2 stars</option>
                                   <option value="1">1 star</option>
                               </select>
                               @error('stars')
                               <div class="fs-md fw-light text-danger">{{ __('front/ricekakis.odaberite_ocjenu_error') }} </div>
                               @enderror
                               <div class="invalid-feedback">{{ __('front/ricekakis.odaberite_ocjenu_error') }}</div>
                           </div>
                           <div class="mb-3">
                               <label class="form-label" for="review-text">{{ __('front/ricekakis.review') }} <span class="text-danger">*</span></label>
                               <textarea class="form-control" rows="6" required id="review-message" name="message"></textarea>
                               @error('message')
                               <div class="fs-md fw-light text-danger">{{ __('front/ricekakis.review_error') }}</div>
                               @enderror
                               <div class="invalid-feedback">{{ __('front/ricekakis.review_error') }} </div>
                           </div>
                           <input type="hidden" name="lang" value="{{ current_locale() }}">
                           <input type="hidden" name="product_id" value="{{ $prod->id }}">
                           <button class="btn btn-primary btn-shadow d-block w-100" type="submit">{{ __('front/ricekakis.posalji') }} </button>
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





    @if (count($related) > 1)
<section class="pb-5 mb-2 mb-xl-4">
<div class=" flex-wrap justify-content-between align-items-center  text-center">
<h4 class="h3 mb-3 pt-1 font-title me-3 text-center"> {{ __('front/ricekakis.povezani_proizvodi') }}</h4>

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
    @endif
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
$('#openReview').on('click', function(e) {
    e.preventDefault();
    $('.nav-tabs a[href="#reviews"]').tab('show');
  document.getElementById("tabs_widget").scrollIntoView();
});
</script>
@endpush
