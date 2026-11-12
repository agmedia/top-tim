@extends('back.layouts.backend')
@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush
@section('content')
    <div class="bg-body-light"><div class="content content-full"><h1 class="font-size-h2 font-w400">{{ $discount->exists ? 'Uredi' : 'Novi' }} količinski popust</h1></div></div>
    <div class="content content-boxed">
        @include('back.layouts.partials.session')
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ $discount->exists ? route('discounts.update', $discount) : route('discounts.store') }}" class="block block-rounded">
            @csrf
            @if($discount->exists) @method('PATCH') @endif
            <div class="block-content">
                <div class="form-group">
                    <label for="discount-target">Popust vrijedi za</label>
                    <select id="discount-target" name="target" class="form-control">
                        <option value="user" @selected(old('target', $discount->user_group_id ? 'group' : 'user') === 'user')>Korisnika</option>
                        <option value="group" @selected(old('target', $discount->user_group_id ? 'group' : 'user') === 'group')>Grupu korisnika</option>
                    </select>
                </div>
                <div class="form-group" id="discount-user-field">
                    <label for="discount-user">Korisnik</label>
                    <select id="discount-user" name="user_id" class="form-control" style="width:100%">
                        @if($selectedUser)<option value="{{ $selectedUser->id }}" selected>{{ $selectedUser->name }} ({{ $selectedUser->email }})</option>@endif
                    </select>
                </div>
                <div class="form-group" id="discount-group-field">
                    <label for="discount-group">Grupa korisnika</label>
                    <select id="discount-group" name="user_group_id" class="form-control" style="width:100%">
                        <option value="">Odaberite grupu</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected((string) old('user_group_id', $discount->user_group_id) === (string) $group->id)>{{ $group->translation?->title ?? $group->id }}{{ $group->status ? '' : ' (neaktivna)' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="discount-brand">Brend</label>
                    <select id="discount-brand" name="brand_id" class="form-control" required style="width:100%">
                        <option value="">Odaberite brend</option>
                        @foreach($brands as $brand)<option value="{{ $brand->id }}" @selected((string) old('brand_id', $discount->brand_id) === (string) $brand->id)>{{ $brand->translation?->title ?? $brand->id }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group" data-discount-tiers>
                    <h2 class="h5">Razine popusta</h2>
                    <p class="text-muted">Primjer: od 5 komada 5 %, od 10 komada 10 %. Svaka razina vrijedi od navedenog broja komada.</p>
                    <div data-tier-list>
                        @foreach(old('tiers', $levels) as $index => $level)
                            @include('back.discount.level', ['index' => $index, 'level' => $level])
                        @endforeach
                    </div>
                    <template data-tier-template>
                        @include('back.discount.level', ['index' => 'new', 'level' => ['active' => true]])
                    </template>
                    <button type="button" class="btn btn-outline-primary mb-2" data-add-tier><i class="fa fa-plus mr-1"></i> Dodaj razinu</button>
                </div>
                <p class="text-muted">Zbrajaju se svi proizvodi i veličine odabranog brenda. Popust se računa od redovne cijene s doplatom za opciju. Ako je postojeća akcijska cijena niža, kupac zadržava tu cijenu.</p>
                <div class="form-group"><button class="btn btn-success" type="submit">Spremi sve razine</button> <a class="btn btn-light" href="{{ route('discounts') }}">Povratak</a></div>
            </div>
        </form>
        @if($discount->exists)
            <form method="POST" action="{{ route('discounts.destroy', $discount) }}" onsubmit="return confirm('Obrisati sve razine popusta za ovaj odabir?')">@csrf @method('DELETE')<input type="hidden" name="all_levels" value="1"><button type="submit" class="btn btn-outline-danger">Obriši sve razine</button></form>
        @endif
    </div>
@endsection
@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/back/discount-tiers.js?v=20261112-1') }}" defer></script>
    <script>
        $(() => {
            $('#discount-user').select2({
                placeholder: 'Pretražite ime ili e-mail', minimumInputLength: 2,
                ajax: {url: @json(route('discounts.users')), dataType: 'json', delay: 250,
                    data: params => ({q: params.term, page: params.page || 1})}
            });
            $('#discount-group, #discount-brand').select2();
            function selectTarget() {
                const user = $('#discount-target').val() === 'user';
                $('#discount-user-field').toggle(user);
                $('#discount-group-field').toggle(!user);
                $('#discount-user').prop('disabled', !user).prop('required', user);
                $('#discount-group').prop('disabled', user).prop('required', !user);
            }
            $('#discount-target').on('change', selectTarget);
            selectTarget();
        });
    </script>
@endpush
