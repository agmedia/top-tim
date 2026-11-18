@if($anchor = $product->anchor_price)
    @if(!empty($anchor['net_quantity']))
        <div class="fs-sm text-muted">{{ number_format($product->main_special / $anchor['net_quantity'], 2, ',', '.') }} € / {{ $anchor['unit'] }}</div>
    @endif
    <div class="{{ $anchorPriceClass ?? 'fs-sm' }} text-muted mt-1 anchor-price">Bazna cijena na {{ $anchor['date'] }}: <span class="text-nowrap">{{ $anchor['formatted'] }}</span></div>
@endif
