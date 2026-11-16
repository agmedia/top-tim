@if($recorded = $product->recorded_lowest_price)
    <div class="fs-xs text-muted mt-1 recorded-price" title="{{ $recorded['note'] }}">Najniža zabilježena cijena: <span class="text-nowrap">{{ $recorded['formatted'] }}</span><small class="d-block">{{ $recorded['note'] }}</small></div>
@endif
