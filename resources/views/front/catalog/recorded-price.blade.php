@if($recorded = $product->recorded_lowest_price)
    <div class="fs-xs text-muted mt-1 recorded-price">Najniža zabilježena cijena: <span class="text-nowrap">{{ $recorded['formatted'] }}</span></div>
@endif
