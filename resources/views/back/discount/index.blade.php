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
        <p>Razine vrijede za sve odabrane brendove. Količine se zbrajaju zasebno za svaki brend. Sve razine uređuju se u jednom obrascu. Primjenjuje se najpovoljnija cijena, bez zbrajanja popusta na artikl.</p>
        <div class="block block-rounded">
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead><tr><th>Popust vrijedi za</th><th>Brendovi</th><th>Razine popusta</th><th>Status</th><th class="text-right">Uredi</th></tr></thead>
                        <tbody>
                        @forelse($discounts as $discount)
                            <tr>
                                <td>
                                    @if($discount->applies_to_everyone)
                                        Svima (gosti i svi prijavljeni kupci)
                                    @elseif($discount->user_id)
                                        {{ $discount->user?->name ?? 'Obrisani korisnik' }}<br><small>{{ $discount->user?->email }}</small>
                                    @elseif($discount->user_group_id)
                                        Grupa: {{ $discount->group?->translation?->title ?? 'Obrisana grupa' }}
                                    @else
                                        Bez korisnika ili grupe (nije javno)
                                    @endif
                                </td>
                                <td>
                                    @foreach($discount->brand_ids ?: ($discount->brand_id ? [$discount->brand_id] : []) as $brandId)
                                        {{ $loop->first ? '' : ', ' }}{{ $brandLabels[$brandId] ?? 'Obrisani brend #' . $brandId }}
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($discount->levels as $level)
                                        <span class="badge {{ $level->active ? 'badge-success' : 'badge-secondary' }} mr-1 mb-1">{{ $level->min_quantity }}+ kom. → {{ number_format($level->percentage, 2, ',', '.') }} %{{ $level->active ? '' : ' (neaktivno)' }}</span>
                                    @endforeach
                                </td>
                                <td>Aktivno {{ $discount->levels->where('active', true)->count() }}/{{ $discount->levels->count() }}</td>
                                <td class="text-right"><a class="btn btn-sm btn-alt-secondary" href="{{ route('discounts.edit', $discount) }}" aria-label="Uredi popust"><i class="fa fa-pencil-alt"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Još nema pravila količinskog popusta.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $discounts->links() }}
            </div>
        </div>
    </div>
@endsection
