@extends('front.layouts.app')
@section('title', 'Zakonsko jamstvo | Top Tim')
@push('meta_tags')<link rel="canonical" href="{{ route('legal-guarantee') }}">@endpush
@section('content')
    <section class="container py-4">
        <h1 class="h2">Zakonsko jamstvo – najmanje 2 godine</h1>
        <p>Potrošači imaju pravo na zakonsko jamstvo za neispravnu ili neusklađenu robu u trajanju od najmanje dvije godine od primitka. Za ostvarivanje prava obratite se na <a href="mailto:info@top-tim.com">info@top-tim.com</a>.</p>
        <p><a href="https://europa.eu/youreurope/jamstva_hr" target="_blank" rel="noopener noreferrer">Više informacija o zakonskom jamstvu – Your Europe</a></p>
        <a href="{{ asset('image/legal-guarantee-notice-hr.svg') }}" target="_blank" rel="noopener"><img class="img-fluid" src="{{ asset('image/legal-guarantee-notice-hr.svg') }}" alt="Službena obavijest Europske unije o zakonskom jamstvu od najmanje dvije godine" width="595" height="842" loading="lazy"></a>
    </section>
@endsection
