
@extends('back.layouts.simple')

@section('content')

    <div class="row no-gutters justify-content-center bg-white ">
        <div class="hero-static col-sm-10 col-md-8 col-xl-6 d-flex align-items-center p-2 px-sm-0">
            <!-- Sign In Block -->
            <div class="block block-rounded block-transparent block-fx-pop w-100 mb-0 overflow-hidden bg-image" >
                <div class="row no-gutters">
                    <div class="col-md-12 bg-white">
                        <div class="block-content block-content-full px-lg-5 py-md-5 py-lg-6">
                            <!-- Header -->
                            <div class="mb-2 text-center">
                                <a class="img-link mr-3" href="{{ route('index') }}">
                                    <img src="{{ asset('image/logo-top-tim.svg') }}"  class="mb-4" width="200" alt="Top Tim - Better way to stay in the game">
                                </a>
                                <p class="text-uppercase font-w700 font-size-sm text-muted">Zaboravljena lozinka</p>
                            </div>

                            <div class="mb-4 text-sm text-gray-600">
                                {{ __('Zaboravili ste lozinku? Nema problema. Upišite svoj email i poslat ćemo vam link za resetiranje lozinki da možete odabrati novu.') }}
                            </div>

                            @if (session('status'))
                                <div class="mb-4 font-medium text-sm text-green-600">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('forget.password.post') }}">
                                @csrf

                                <div class="block">
                                    <x-jet-label for="email" value="{{ __('Email') }}" />
                                    <x-jet-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                                </div>

                                <div class="flex items-center justify-end mt-4">
                                    <x-jet-button class="btn btn-block btn-hero-primary">
                                        {{ __('Resetiraj lozinku') }}
                                    </x-jet-button>
                                </div>
                            </form>

                            <x-jet-validation-errors class="mb-4" />

                        </div>
                    </div>

                </div>
            </div>
            <!-- END Sign In Block -->
        </div>
    </div>

@endsection
