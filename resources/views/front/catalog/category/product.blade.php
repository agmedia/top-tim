<div class="article pb-1" >

    <div class="card product-card d-flex align-items-stretch  pb-1">

        @if ($product->main_price > $product->main_special)
            <span class="badge bg-red badge-shadow">-{{ number_format(floatval(\App\Helpers\Helper::calculateDiscount($product->price, $product->special())), 0) }}%</span>
        @endif
        <a class="card-img-top pb-2 d-block overflow-hidden" href="{{ url($product->url) }}">
            <img load="lazy" src="{{ str_replace('.webp','-thumb.webp', $product->image) }}" width="400" height="400" alt="{{ $product->name }}">
        </a>
        <div class="card-body pt-2" style="min-height: 120px;">

            <h3 class="product-title fs-sm text-truncate"><a href="{{ url($product->url) }}">{{ $product->name }}</a></h3>
            {!! $product->category_string !!}
            @if ($product->main_price > $product->main_special)
                <div class="product-price"><small><span class="text-muted">{{ __('front/ricekakis.nc_30') }}: {{ $product->main_price_text }}  @if($product->secondary_price_text){{ $product->secondary_price_text }} @endif</span></small></div>
                <div class="product-price text-red"><span class="text-red fs-md">{{ $product->main_special_text }} @if($product->secondary_special_text) <small class="text-muted">{{ $product->secondary_special_text }}</small> @endif</span></div>
            @else
                <div class="product-price"><span class="text-dark fs-md">{{ $product->main_price_text }}  @if($product->secondary_price_text) <small class="fs-sm text-muted">{{ $product->secondary_price_text }} </small>@endif</span></div>
            @endif


            @if($product->reviews->count() > 1)
                <div class="star-rating">
                    @for ($i = 0; $i < 5; $i++)
                        @if (floor($product->reviews->avg('stars')) - $i >= 1)
                            {{--Full Start--}}
                            <i class="star-rating-icon ci-star-filled active"></i>
                        @elseif ($product->reviews->avg('stars') - $i > 0)
                            {{--Half Start--}}
                            <i class="star-rating-icon ci-star-half active"></i>
                        @else
                            {{--Empty Start--}}
                            <i class="star-rating-icon ci-star"></i>
                        @endif
                    @endfor
                </div>
            @endif


        </div>
            @if($product->quantity > 0 && !$product->has_option)
               <div class="product-floating-btn">
                   <add-to-cart-btn-simple id="{{ $product->id }}" available="{{ $product->quantity }}"></add-to-cart-btn-simple>
              </div>
            @endif

            @if($product->quantity > 0 && $product->has_option)
                <div class="product-floating-btn">
                    <a class="btn btn-primary btn-shadow btn-sm" href="{{ url($product->url) }}">+<i class="ci-cart fs-base ms-1"></i></a>
                </div>
            @endif
    </div>
</div>

