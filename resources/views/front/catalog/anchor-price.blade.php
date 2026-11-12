@if($anchor = $product->anchor_price)
    <div class="fs-sm text-muted mt-1 anchor-price">Cijena na {{ $anchor['date'] }}: <span class="text-nowrap">{{ $anchor['formatted'] }}</span></div>
@endif
