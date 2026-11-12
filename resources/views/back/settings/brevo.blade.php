@extends('back.layouts.backend')

@section('content')
    <div class="content">
        <h1>Brevo newsletter</h1>
        @include('back.layouts.partials.session')

        <form method="post" action="{{ route('brevo.update') }}">
            @csrf
            <input type="hidden" name="enabled" value="0">
            <div class="form-group">
                <label><input type="checkbox" name="enabled" value="1" @if(old('enabled', $enabled)) checked @endif> Šalji nove prijave u Brevo</label>
            </div>
            <div class="form-group">
                <label for="brevo-list">ID liste u Brevu</label>
                <input class="form-control" type="number" min="1" name="list_id" id="brevo-list" value="{{ old('list_id', $listId) }}">
                @error('list_id') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="brevo-key">Brevo API ključ</label>
                <input class="form-control" type="password" name="brevo_api_key" id="brevo-key" autocomplete="new-password">
                @if($hasKey) <small>Ključ je spremljen. Ostavite polje prazno za zadržavanje postojećeg ključa.</small> @endif
                @error('brevo_api_key') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <button class="btn btn-primary" type="submit">Spremi</button>
        </form>
    </div>
@endsection
