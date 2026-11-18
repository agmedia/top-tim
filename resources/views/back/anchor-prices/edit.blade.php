@extends('back.layouts.backend')
@section('content')
    <div class="content">
        <h1 class="font-size-h2">Bazna cijena: {{ $product->name }}</h1>
        @include('back.layouts.partials.session')
        @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <p>Šifra: {{ $product->sku }} · Redovna cijena: {{ number_format($product->price, 2, ',', '.') }} €. Potvrđena bazna cijena prikazuje se na frontu. Izmjena ne mijenja prodajnu cijenu ni već objavljene cjenike.</p>
        <div class="block block-rounded"><div class="block-content pb-3">
            <form method="post" action="{{ route('anchor-prices.update', $product->id) }}">@csrf @method('PATCH')
                <div class="form-group"><label for="anchor-price">Bazna maloprodajna cijena s PDV-om (EUR)</label><input class="form-control" id="anchor-price" name="price" inputmode="decimal" value="{{ old('price', $anchor?->price) }}" required></div>
                <div class="form-group"><label for="anchor-group">Skupina za određivanje datuma</label><select class="form-control" id="anchor-group" name="reference_group">
                    <option value="">Prema postojećoj evidenciji</option>
                    <option value="general" @selected(old('reference_group', $anchor?->reference_group) === 'general')>Sportska oprema i ostala roba (10. 9. 2026.)</option>
                    <option value="household" @selected(old('reference_group', $anchor?->reference_group) === 'household')>Hrana, piće, kozmetika, sredstva za čišćenje, toaletne potrepštine i proizvodi za kućanstvo (2. 5. 2025.)</option>
                </select></div>
                <div class="form-group"><label for="anchor-first">Datum prve ponude, ako je poznat</label><input class="form-control" id="anchor-first" type="date" name="first_offered_on" max="{{ now()->toDateString() }}" value="{{ old('first_offered_on', $anchor?->first_offered_on) }}"></div>
                <div class="form-group"><label for="anchor-date">Referentni datum</label><input class="form-control" id="anchor-date" name="reference_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('reference_date', $anchor?->reference_date?->toDateString()) }}" required></div>
                <p>Neto količinu i jedinicu popunite za robu kojoj se ističe cijena za jedinicu mjere. Za 250 ml upišite 0,25 i odaberite l.</p>
                <div class="form-group"><label for="anchor-net">Neto količina pakiranja</label><input class="form-control" id="anchor-net" name="net_quantity" inputmode="decimal" value="{{ old('net_quantity', $anchor?->net_quantity) }}"></div>
                <div class="form-group"><label for="anchor-unit">Jedinica mjere</label><select class="form-control" id="anchor-unit" name="measure_unit"><option value="">Bez posebne mjere</option>@foreach(['kg','l','m','m²','m³','kom'] as $unit)<option @selected(old('measure_unit', $anchor?->measure_unit) === $unit)>{{ $unit }}</option>@endforeach</select></div>
                <div class="form-group"><label for="anchor-barcode">Barkod robe, ako je različit od EAN-a</label><input class="form-control" id="anchor-barcode" name="goods_barcode" maxlength="100" value="{{ old('goods_barcode', $anchor?->goods_barcode) }}"></div>
                <div class="form-group"><label for="anchor-state">Status</label><select class="form-control" id="anchor-state" name="status"><option value="pending" @selected(old('status', $anchor?->status) !== 'confirmed')>Za provjeru — nije javno prikazano</option><option value="confirmed" @selected(old('status', $anchor?->status) === 'confirmed')>Potvrđeno prema evidenciji</option></select></div>
                <div class="form-group"><label for="anchor-reason">Izvor potvrde / razlog izmjene</label><textarea class="form-control" id="anchor-reason" name="note" required minlength="3" maxlength="2000" rows="3">{{ old('note') }}</textarea>@if($anchor)<small>Zadnji razlog: {{ $anchor->note }}</small>@endif</div>
                <button class="btn btn-success" type="submit">Spremi</button> <a class="btn btn-light" href="{{ route('anchor-prices.index') }}">Povratak</a>
            </form>
        </div></div>
        @if($anchor && $options->isNotEmpty())
            <details class="block block-rounded p-3"><summary>Bazne cijene i kodovi varijanti ({{ $options->count() }})</summary>
                <p>Varijanta sa zajedničkom cijenom koristi potvrdu proizvoda. Za zasebnu povijesnu cijenu, doplatu ili vlastiti EAN unesite podatke varijante.</p>
                @foreach($options as $option)
                    @php($value = $anchor->variant_prices[$option->parent_id . ':' . $option->option_id] ?? [])
                    <details class="mb-2"><summary>{{ $option->sku ?: 'Opcija ' . $option->id }} · doplata {{ number_format($option->price, 2, ',', '.') }} € · {{ ($value['status'] ?? null) === 'confirmed' ? 'potvrđena' : ((float) $option->price != 0 ? 'potrebna potvrda' : 'zajednička cijena') }}</summary>
                        <form class="mt-2" method="post" action="{{ route('anchor-prices.variant', [$product->id, $option->id]) }}">@csrf @method('PATCH')
                            <label>Bazna cijena (€) <input name="price" required inputmode="decimal" value="{{ $value['price'] ?? '' }}"></label>
                            <label>Datum <input type="date" name="reference_date" required value="{{ $value['reference_date'] ?? '' }}"></label>
                            <label>EAN <input name="ean" value="{{ $value['ean'] ?? '' }}"></label>
                            <label>Drugi barkod <input name="goods_barcode" value="{{ $value['goods_barcode'] ?? '' }}"></label>
                            <label>Izvor potvrde <input name="note" required minlength="3" maxlength="2000"></label>
                            <input type="hidden" name="status" value="confirmed"><button class="btn btn-sm btn-primary">Potvrdi varijantu</button>
                        </form>
                    </details>
                @endforeach
            </details>
        @endif
        <h2 class="h4">Povijest izmjena</h2>
        <div class="table-responsive"><table class="table"><thead><tr><th>Vrijeme</th><th>Cijena / datum / status</th><th>Razlog</th><th>Korisnik</th></tr></thead><tbody>
            @foreach($revisions as $revision)@php($after = json_decode($revision->after, true))
                <tr><td>{{ \Carbon\Carbon::parse($revision->created_at)->timezone('Europe/Zagreb')->format('d.m.Y. H:i') }}</td><td>{{ isset($after['sku']) ? $after['sku'] . ' · ' : '' }}{{ number_format($after['price'], 2, ',', '.') }} €<br>{{ \Carbon\Carbon::parse($after['reference_date'])->format('d.m.Y.') }} · {{ $after['status'] === 'confirmed' ? 'Potvrđeno' : 'Za provjeru' }}</td><td>{{ $revision->reason }}</td><td>{{ $revision->user_id ?? 'Sustav' }}</td></tr>
            @endforeach
        </tbody></table></div>{{ $revisions->links() }}
    </div>
@endsection
