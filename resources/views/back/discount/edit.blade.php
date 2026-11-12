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
                <div class="row">
                    <div class="form-group col-md-6"><label for="discount-quantity">Minimalni broj komada brenda</label><input id="discount-quantity" name="min_quantity" type="number" min="1" max="1000000" step="1" required class="form-control" value="{{ old('min_quantity', $discount->min_quantity) }}"></div>
                    <div class="form-group col-md-6"><label for="discount-percentage">Popust (%)</label><input id="discount-percentage" name="percentage" type="number" min="0.01" max="100" step="0.01" required class="form-control" value="{{ old('percentage', $discount->percentage) }}"></div>
                </div>
                <p class="text-muted">Zbrajaju se svi proizvodi i veličine odabranog brenda. Popust se računa od redovne cijene s doplatom za opciju. Ako je postojeća akcijska cijena niža, kupac zadržava tu cijenu.</p>
                <div class="form-group"><input type="hidden" name="active" value="0"><label><input type="checkbox" name="active" value="1" @checked(old('active', $discount->active))> Aktivno</label></div>
                <div class="form-group"><button class="btn btn-success" type="submit">Spremi popust</button> <a class="btn btn-light" href="{{ route('discounts') }}">Povratak</a></div>
            </div>
        </form>
        @if($discount->exists)
            <form method="POST" action="{{ route('discounts.destroy', $discount) }}" onsubmit="return confirm('Obrisati ovaj količinski popust?')">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger">Obriši popust</button></form>
        @endif
    </div>
@endsection
@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
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
