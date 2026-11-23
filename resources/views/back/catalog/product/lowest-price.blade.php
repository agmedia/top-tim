@php
    $lowestCorrection = $lowestPriceData['correction'] ?? null;
    $lowestManualActive = $lowestPriceData['manual_active'] ?? false;
    $lowestOldInput = in_array(old('mode'), ['manual', 'automatic'], true);
    $lowestInputPrice = $lowestOldInput
        ? old('price')
        : (isset($lowestPriceData['effective']['price']) ? number_format($lowestPriceData['effective']['price'], 2, ',', '') : '');
    $lowestErrors = $errors->getBag('lowestPrice');
@endphp
<section class="block block-rounded" id="lowest-price" aria-labelledby="lowest-price-heading">
    <div class="block-header block-header-default">
        <h2 class="block-title" id="lowest-price-heading">{{ __('back/products.najniza_zabiljezena_cijena') }}</h2>
    </div>
    <div class="block-content pb-3">
        <div class="row mb-2">
            <div class="col-md-6 mb-2">
                {{ __('back/products.najniza_vazeci_iznos') }}: <strong>{{ $lowestPriceData['effective']['formatted'] ?? '—' }}</strong>
            </div>
            <div class="col-md-6 mb-2">
                {{ __('back/products.najniza_automatski_iznos') }}: <strong>{{ $lowestPriceData['automatic']['formatted'] ?? '—' }}</strong>
            </div>
        </div>
        @if ($lowestManualActive && $lowestCorrection?->expires_at)
            <p class="text-muted">{{ __('back/products.najniza_rucna_do', ['date' => \Illuminate\Support\Carbon::parse($lowestCorrection->expires_at)->timezone('Europe/Zagreb')->format('d.m.Y. H:i')]) }}</p>
        @else
            <p class="text-muted">{{ __('back/products.najniza_automatski_prikaz') }}</p>
        @endif

        @if ($canCorrectLowestPrice ?? false)
            @if ($lowestErrors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($lowestErrors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif
            <p class="small text-muted" id="lowest-price-help">{{ __('back/products.najniza_uputa') }}</p>
            <form id="lowest-price-form" method="POST" action="{{ route('products.lowest-price.update', ['product' => $product->id]) }}">
                @csrf
                @method('PATCH')
                <div class="form-group row">
                    <div class="col-md-4">
                        <label for="lowest-price-amount">{{ __('back/products.najniza_zabiljezena_cijena') }} (€)</label>
                        <div class="input-group">
                            <input type="text" class="form-control @if($lowestErrors->has('price')) is-invalid @endif" id="lowest-price-amount" name="price" inputmode="decimal" placeholder="0,00" value="{{ $lowestInputPrice }}" aria-describedby="lowest-price-help">
                            <div class="input-group-append"><span class="input-group-text">€</span></div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label for="lowest-price-reason">{{ __('back/products.najniza_razlog') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control @if($lowestErrors->has('reason')) is-invalid @endif" id="lowest-price-reason" name="reason" required minlength="3" maxlength="1000" rows="2">{{ $lowestOldInput ? old('reason') : '' }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mr-2 mb-2" name="mode" value="manual">{{ __('back/products.snimi_najnizu_cijenu') }}</button>
                <button type="submit" class="btn btn-outline-secondary mb-2" name="mode" value="automatic">{{ __('back/products.najniza_vrati_automatski') }}</button>
            </form>
        @else
            <p class="small text-muted">{{ __('back/products.najniza_samo_admin') }}</p>
        @endif

        @if (!empty($lowestPriceData['history']) && $lowestPriceData['history']->isNotEmpty())
            <details class="mt-3">
                <summary>{{ __('back/products.najniza_povijest') }}</summary>
                <div class="table-responsive mt-2">
                    <table class="table table-sm">
                        <thead><tr>
                            <th>{{ __('back/products.najniza_vrijeme') }}</th>
                            <th>{{ __('back/products.najniza_promjena') }}</th>
                            <th>{{ __('back/products.cijena') }}</th>
                            <th>{{ __('back/products.najniza_vrijedi_do') }}</th>
                            <th>{{ __('back/products.najniza_razlog') }}</th>
                            <th>{{ __('back/products.najniza_korisnik') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($lowestPriceData['history'] as $entry)
                                <tr>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->timezone('Europe/Zagreb')->format('d.m.Y. H:i') }}</td>
                                    <td>{{ __($entry->mode === 'manual' ? 'back/products.najniza_rucni_ispravak' : 'back/products.najniza_automatski_iznos') }}</td>
                                    <td class="text-nowrap">{{ $entry->price !== null ? number_format((float) $entry->price, 2, ',', '.') . ' €' : '—' }}</td>
                                    <td class="text-nowrap">{{ $entry->expires_at ? \Illuminate\Support\Carbon::parse($entry->expires_at)->timezone('Europe/Zagreb')->format('d.m.Y. H:i') : '—' }}</td>
                                    <td>{{ $entry->reason }}</td>
                                    <td>{{ $entry->actor_id ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </div>
</section>
