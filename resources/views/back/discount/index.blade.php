@extends('back.layouts.backend')
@section('content')
    <div class="bg-body-light">
        <div class="content content-full d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="font-size-h2 font-w400 mb-2">Količinski popusti</h1>
            <a class="btn btn-hero-success mb-2" href="{{ route('discounts.create') }}"><i class="fa fa-plus mr-1"></i> Novi popust</a>
        </div>
    </div>
    <div class="content">
        @include('back.layouts.partials.session')
        <p>Prag se računa po ukupnom broju komada istog brenda u košarici. Za više pragova dodajte više pravila. Primjenjuje se najpovoljnija cijena, bez zbrajanja popusta na artikl.</p>
        <div class="block block-rounded">
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead><tr><th>Korisnik / grupa</th><th>Brend</th><th>Od komada</th><th>Popust</th><th>Status</th><th class="text-right">Uredi</th></tr></thead>
                        <tbody>
                        @forelse($discounts as $discount)
                            <tr>
                                <td>
                                    @if($discount->user_id)
                                        {{ $discount->user?->name ?? 'Obrisani korisnik' }}<br><small>{{ $discount->user?->email }}</small>
                                    @else
                                        Grupa: {{ $discount->group?->translation?->title ?? 'Obrisana grupa' }}
                                    @endif
                                </td>
                                <td>{{ $discount->brand?->translation?->title ?? 'Obrisani brend' }}</td>
                                <td>{{ $discount->min_quantity }}</td>
                                <td>{{ number_format($discount->percentage, 2, ',', '.') }} %</td>
                                <td>{{ $discount->active ? 'Aktivno' : 'Neaktivno' }}</td>
                                <td class="text-right"><a class="btn btn-sm btn-alt-secondary" href="{{ route('discounts.edit', $discount) }}" aria-label="Uredi popust"><i class="fa fa-pencil-alt"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Još nema pravila količinskog popusta.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $discounts->links() }}
            </div>
        </div>
    </div>
@endsection
