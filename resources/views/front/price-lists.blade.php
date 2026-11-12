@extends('front.layouts.app')
@section('title', 'Cjenici | Top Tim')
@push('meta_tags')<link rel="canonical" href="{{ route('price-lists.index') }}">@endpush
@section('content')
    <section class="container py-4">
        <h1 class="h2">Cjenici</h1>
        <p>Preuzmite dnevni XML cjenik webshopa Top Tim. Cijene su izražene u eurima s PDV-om. Cjenik uključuje redovne, akcijske i potvrđene sidrene cijene te dostupnost proizvoda u trenutku objave.</p>
        <p class="text-muted">Cjenici su dostupni 30 dana od objave. Osobni količinski popusti ovise o korisniku i sadržaju košarice.</p>
        @if($publications->count())
            <a class="btn btn-primary mb-4" href="{{ route('price-lists.latest') }}">Preuzmi najnoviji XML cjenik</a>
            <div class="table-responsive"><table class="table table-striped">
                <thead><tr><th>Datum objave</th><th>Prodajno mjesto</th><th>Proizvodi</th><th>Preuzimanje</th></tr></thead>
                <tbody>@foreach($publications as $publication)<tr><td>{{ $publication->published_at->timezone('Europe/Zagreb')->format('d.m.Y. H:i') }}</td><td>Top Tim webshop</td><td>{{ $publication->product_count }}</td><td><a href="{{ route('price-lists.download', $publication) }}" aria-label="Preuzmi XML cjenik za {{ $publication->publication_date->format('d.m.Y.') }}">XML cjenik</a></td></tr>@endforeach</tbody>
            </table></div>{{ $publications->links() }}
        @else<div class="alert alert-info">Cjenici su u pripremi.</div>@endif
    </section>
@endsection
