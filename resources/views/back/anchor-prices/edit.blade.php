@extends('back.layouts.backend')
@section('content')
    <div class="content">
        <h1 class="font-size-h2">Sidrena cijena: {{ $product->name }}</h1>
        @include('back.layouts.partials.session')
        @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <p>Šifra: {{ $product->sku }} · Redovna cijena: {{ number_format($product->price, 2, ',', '.') }} €. Potvrđena sidrena cijena prikazuje se na frontu kada je uključen prikaz u postavkama modula. Izmjena ne mijenja prodajnu cijenu ni već objavljene cjenike.</p>
        <div class="block block-rounded"><div class="block-content pb-3">
            <form method="post" action="{{ route('anchor-prices.update', $product->id) }}">@csrf @method('PATCH')
                <div class="form-group"><label for="anchor-price">Sidrena maloprodajna cijena s PDV-om (EUR)</label><input class="form-control" id="anchor-price" name="price" inputmode="decimal" value="{{ old('price', $anchor?->price) }}" required></div>
                <div class="form-group"><label for="anchor-date">Referentni datum</label><input class="form-control" id="anchor-date" name="reference_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('reference_date', $anchor?->reference_date?->toDateString() ?? config('price_lists.reference_date')) }}" required></div>
                <div class="form-group"><label for="anchor-state">Status</label><select class="form-control" id="anchor-state" name="status"><option value="pending" @selected(old('status', $anchor?->status) !== 'confirmed')>Za provjeru — nije javno prikazano</option><option value="confirmed" @selected(old('status', $anchor?->status) === 'confirmed')>Potvrđeno prema evidenciji</option></select></div>
                <div class="form-group"><label for="anchor-reason">Izvor potvrde / razlog izmjene</label><textarea class="form-control" id="anchor-reason" name="note" required minlength="3" maxlength="2000" rows="3">{{ old('note') }}</textarea>@if($anchor)<small>Zadnji razlog: {{ $anchor->note }}</small>@endif</div>
                <button class="btn btn-success" type="submit">Spremi</button> <a class="btn btn-light" href="{{ route('anchor-prices.index') }}">Povratak</a>
            </form>
        </div></div>
        <h2 class="h4">Povijest izmjena</h2>
        <div class="table-responsive"><table class="table"><thead><tr><th>Vrijeme</th><th>Cijena / datum / status</th><th>Razlog</th><th>Korisnik</th></tr></thead><tbody>
            @foreach($revisions as $revision)@php($after = json_decode($revision->after, true))
                <tr><td>{{ \Carbon\Carbon::parse($revision->created_at)->timezone('Europe/Zagreb')->format('d.m.Y. H:i') }}</td><td>{{ number_format($after['price'], 2, ',', '.') }} €<br>{{ \Carbon\Carbon::parse($after['reference_date'])->format('d.m.Y.') }} · {{ $after['status'] === 'confirmed' ? 'Potvrđeno' : 'Za provjeru' }}</td><td>{{ $revision->reason }}</td><td>{{ $revision->user_id ?? 'Sustav' }}</td></tr>
            @endforeach
        </tbody></table></div>{{ $revisions->links() }}
    </div>
@endsection
