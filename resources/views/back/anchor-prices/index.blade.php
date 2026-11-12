@extends('back.layouts.backend')
@section('content')
    <div class="bg-body-light"><div class="content content-full"><h1 class="font-size-h2 font-w400">Sidrene cijene i XML cjenici</h1></div></div>
    <div class="content">
        @include('back.layouts.partials.session')
        @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <div class="block block-rounded"><div class="block-content pb-3">
            <h2 class="h4">Prikaz sidrenih cijena</h2>
            <p>Status prikaza: <strong>{{ $displayEnabled ? 'Uključeno' : 'Isključeno' }}</strong></p>
            <form method="post" action="{{ route('anchor-prices.settings') }}">
                @csrf @method('PATCH')
                <input type="hidden" name="display_enabled" value="0">
                <div class="custom-control custom-switch custom-control-success mb-3">
                    <input type="checkbox" class="custom-control-input" id="anchor-display-switch" name="display_enabled" value="1" @checked(old('display_enabled', $displayEnabled)) aria-describedby="anchor-display-help">
                    <label class="custom-control-label" for="anchor-display-switch">Prikazuj sidrene cijene na webshopu</label>
                </div>
                <p class="text-muted" id="anchor-display-help">Postavka vrijedi za proizvode, kartice i pretragu nakon osvježavanja stranice. Spremljene cijene i XML cjenici ostaju sačuvani; automatska objava cjenika nastavlja raditi.</p>
                <button class="btn btn-primary" type="submit">Spremi prikaz</button>
            </form>
        </div></div>
        <p>Sidrena cijena je potvrđena maloprodajna cijena u EUR s PDV-om na referentni datum. Za postojeći asortiman referentni datum je 10.09.2026., a za kasnije proizvode datum i cijena prvog uvrštenja u prodaju.</p>
        <div class="alert {{ $missing ? 'alert-warning' : 'alert-success' }}">Aktivni proizvodi bez potvrđene sidrene cijene: <strong>{{ $missing }}</strong>. Na frontu se prikazuju samo potvrđene cijene.</div>
        <div class="block block-rounded"><div class="block-content pb-3">
            <h2 class="h4">Dnevni XML cjenik</h2>
            <p>Automatska objava svaki dan u 07:00 (Europe/Zagreb). Ponovni poziv vraća isti dnevni cjenik. <a href="{{ route('price-lists.index') }}" target="_blank" rel="noopener">Javna arhiva</a> · <a href="{{ route('price-lists.latest') }}">Najnoviji XML</a></p>
            @if($latest)
                <p>Zadnja objava: <strong>{{ $latest->published_at->timezone('Europe/Zagreb')->format('d.m.Y. H:i') }}</strong> · {{ $latest->product_count }} proizvoda.</p>
                @if($latest->publication_date->toDateString() !== now('Europe/Zagreb')->toDateString())<div class="alert alert-warning">Današnji cjenik još nije objavljen. Provjerite sidrene cijene i raspored zadataka.</div>@endif
                @if($latest->missing_barcodes)<div class="alert alert-warning">{{ $latest->missing_barcodes }} proizvoda nema valjan barkod. Dopunite EAN u katalogu.</div>@endif
            @else<p>Još nema objavljenog cjenika.</p>@endif
            <form action="{{ route('anchor-prices.publish') }}" method="post">@csrf<button class="btn btn-primary" type="submit">Objavi današnji cjenik</button></form>
        </div></div>
        <div class="block block-rounded"><div class="block-content pb-3">
            <h2 class="h4">Masovni unos</h2>
            <p><a href="{{ route('anchor-prices.export') }}">Preuzmi CSV za uređivanje</a>. Nepotvrđeni prijedlozi koriste današnju redovnu cijenu i status <code>pending</code>. Provjerite povijesni iznos i datum prije promjene statusa u <code>confirmed</code>. CSV ne dokazuje povijest cijena.</p>
            <form action="{{ route('anchor-prices.import') }}" method="post" enctype="multipart/form-data">@csrf
                <div class="form-group"><label for="anchor-file">CSV datoteka (do 5 MB / 10.000 redaka)</label><input class="form-control-file" type="file" id="anchor-file" name="file" accept=".csv,text/csv" required></div>
                <div class="form-group"><label for="anchor-note">Izvor potvrde / razlog izmjene</label><input class="form-control" id="anchor-note" name="note" value="{{ old('note') }}" required minlength="3" maxlength="2000"></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="dry_run" value="1" id="anchor-dry" checked><label class="form-check-label" for="anchor-dry">Samo provjera, bez spremanja</label></div>
                <button class="btn btn-secondary" type="submit">Uvezi CSV</button>
            </form>
        </div></div>
        <form class="row mb-3" method="get">
            <div class="col-sm-6 mb-2"><label class="sr-only" for="anchor-q">Naziv, šifra ili ID</label><input class="form-control" id="anchor-q" name="q" placeholder="Naziv, šifra ili ID proizvoda" value="{{ request('q') }}"></div>
            <div class="col-sm-4 mb-2"><label class="sr-only" for="anchor-status">Status</label><select class="form-control" name="status" id="anchor-status"><option value="">Svi statusi</option value="pending" @selected(request('status') === 'pending')>Za provjeru / bez cijene</option><option value="confirmed" @selected(request('status') === 'confirmed')>Potvrđeno</option></select></div>
            <div class="col-sm-2"><button class="btn btn-primary" type="submit">Prikaži</button></div>
        </form>
        <div class="block block-rounded"><div class="block-content"><div class="table-responsive"><table class="table table-striped table-vcenter">
            <thead><tr><th>Proizvod</th><th>Redovna cijena</th><th>Sidrena cijena</th><th>Datum</th><th>Status</th><th></th></tr></thead>
            <tbody>@forelse($products as $product)<tr>
                <td>{{ $product->name }}<br><small>{{ $product->sku }} · ID {{ $product->id }}{{ $product->status ? '' : ' · Neaktivan' }}</small></td>
                <td>{{ number_format($product->price, 2, ',', '.') }} €</td><td>{{ $product->anchor_price !== null ? number_format($product->anchor_price, 2, ',', '.') . ' €' : '—' }}</td>
                <td>{{ $product->reference_date ? \Carbon\Carbon::parse($product->reference_date)->format('d.m.Y.') : '—' }}</td>
                <td><span class="badge {{ $product->anchor_status === 'confirmed' ? 'badge-success' : 'badge-warning' }}">{{ $product->anchor_status === 'confirmed' ? 'Potvrđeno' : 'Za provjeru' }}</span></td>
                <td><a class="btn btn-sm btn-alt-secondary" href="{{ route('anchor-prices.edit', $product->id) }}">Uredi</a></td>
            </tr>@empty<tr><td colspan="6">Nema proizvoda za zadane kriterije.</td></tr>@endforelse</tbody>
        </table></div>{{ $products->links() }}</div></div>
    </div>
@endsection
