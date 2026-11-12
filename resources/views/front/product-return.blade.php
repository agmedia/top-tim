@extends('front.layouts.app')
@section('title', 'Povrat proizvoda | Top Tim')
@push('meta_tags')<link rel="canonical" href="{{ route('product-return') }}">@endpush
@section('content')
    <section class="container py-4" style="max-width: 860px;">
        <h1 class="h2">Povrat proizvoda</h1>
        <p>Želite vratiti proizvod kupljen na webshopu? Ispunite obrazac za jednostrani raskid ugovora. Potvrdu primitka i upute za povrat šaljemo e-mailom.</p>
        <p class="fs-sm text-muted">Za neispravan proizvod ili reklamaciju obratite se na <a href="mailto:{{ config('mail.contract_termination_to') }}">{{ config('mail.contract_termination_to') }}</a>. <a href="{{ route('contract-termination') }}">Uvjeti i upute za jednostrani raskid</a></p>
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <form id="product-return-form" action="{{ route('product-return.send') }}" method="post" class="card card-body shadow-sm">
            @csrf
            <input name="website" class="d-none" tabindex="-1" autocomplete="off" aria-hidden="true">
            <div class="row g-3">
                @foreach([
                    ['full_name', 'Ime i prezime', 'text', 'name', 150, 'col-md-6'],
                    ['email', 'E-mail', 'email', 'email', 190, 'col-md-6'],
                    ['order_number', 'Broj narudžbe ili računa', 'text', 'off', 80, 'col-12'],
                    ['address', 'Ulica i kućni broj', 'text', 'street-address', 190, 'col-12'],
                    ['postal_code', 'Poštanski broj', 'text', 'postal-code', 20, 'col-md-3'],
                    ['city', 'Mjesto', 'text', 'address-level2', 100, 'col-md-6'],
                    ['country', 'Država', 'text', 'country-name', 80, 'col-md-3'],
                ] as [$field, $label, $type, $autocomplete, $max, $column])
                    <div class="{{ $column }}"><label class="form-label" for="return-{{ $field }}">{{ $label }} *</label><input class="form-control @error($field) is-invalid @enderror" id="return-{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" maxlength="{{ $max }}" value="{{ old($field, $field === 'country' ? 'Hrvatska' : '') }}" required>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                @endforeach
                <div class="col-12"><label class="form-label" for="return-items">Proizvodi za povrat (naziv, veličina i količina) *</label><textarea class="form-control @error('items') is-invalid @enderror" id="return-items" name="items" rows="3" maxlength="3000" required>{{ old('items') }}</textarea>@error('items')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <details class="my-3" @if(old('phone') || old('received_date') || old('iban')) open @endif><summary>Dodatni podaci (neobavezno)</summary><div class="row g-3 mt-1">
                <div class="col-md-6"><label class="form-label" for="return-phone">Telefon</label><input class="form-control" id="return-phone" name="phone" type="tel" maxlength="50" value="{{ old('phone') }}"></div>
                <div class="col-md-6"><label class="form-label" for="return-date">Datum primitka robe</label><input class="form-control" id="return-date" name="received_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('received_date') }}"></div>
                <div class="col-12"><label class="form-label" for="return-iban">IBAN (samo ako je potreban za povrat)</label><input class="form-control" id="return-iban" name="iban" maxlength="50" value="{{ old('iban') }}"><small>Povrat se u pravilu izvršava istim sredstvom plaćanja.</small></div>
            </div></details>
            <div class="form-check my-3"><input class="form-check-input" type="checkbox" name="statement" value="1" id="return-statement" required @checked(old('statement'))><label class="form-check-label" for="return-statement">Izjavljujem da jednostrano raskidam ugovor o kupnji navedenih proizvoda. *</label></div>
            @if(config('services.recaptcha.sitekey'))<input type="hidden" name="recaptcha" id="product-return-recaptcha">@endif
            <div><button class="btn btn-primary" type="submit">Pošalji zahtjev za povrat</button></div>
            @include('front.layouts.partials.recaptcha-notice', ['recaptchaError' => $errors->first('recaptcha')])
            <small class="text-muted mt-3">Podatke koristimo za obradu zahtjeva za povrat. Razlog povrata nije obavezan.</small>
        </form>
    </section>
@endsection
@push('js_after')
    @include('front.layouts.partials.recaptcha-js', ['action' => 'contract_termination', 'fieldId' => 'product-return-recaptcha', 'formId' => 'product-return-form'])
@endpush
