@extends('back.layouts.simple')

@section('content')

    <div class="row no-gutters justify-content-center bg-white " >

        @if (session('message'))
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ dd(session()->all()) }}
            </div>
        @endif

        <div class="hero-static col-sm-10 col-md-8 col-xl-4 d-flex align-items-center p-2 px-sm-0">
            <!-- Sign In Block -->
            <div class="block block-rounded block-transparent block-fx-pop w-100 mb-0 overflow-hidden bg-image" >
                <div class="row no-gutters">
                    <div class="col-md-12 order-md-1 bg-white">
                        <div class="block-content block-content-full px-lg-5 py-md-5 py-lg-6">
                            <!-- Header -->
                            <div class="mb-2 text-center">
                                <a  href="{{ route('index') }}">
                                    <img src="{{ asset('image/logo-top-tim.svg') }}"  class="mb-4" width="200" alt="Top Tim - Better way to stay in the game">
                                </a>
                                <h3>Prijavite se</h3>
                            </div>
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="form-group">
                                    <input type="text" class="form-control form-control-alt" id="email" name="email" value="{{ old('email') }}" placeholder="Email">
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control form-control-alt" id="password" name="password" placeholder="Lozinka">
                                    @if ($errors->has('email'))
                                        <span class="ml-2 font-size-sm text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="remember_me" class="flex items-center">
                                        <x-jet-checkbox id="remember_me" name="remember" />
                                        <span class="ml-2 text-sm text-gray-600">{{ __('Zapamti me') }}</span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-block btn-hero-primary">
                                        <i class="fa fa-fw fa-sign-in-alt mr-1"></i> Prijavi se
                                    </button>
                                </div>
                            </form>
                            <div class="mb-2 text-center">
                                @if (Route::has('password.request'))
                                    <a class="link-fx font-size-sm" href="{{ route('password.request') }}">
                                        {{ __('Zaboravili ste lozinku?') }}
                                    </a>
                                @endif
                            </div>
                            <div class="mb-2 text-center">
                                <a class="link-fx font-size-sm" href="{{ route('register') }}">
                                    {{ __('Nemate račun? Registrirajte se') }}
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- END Sign In Block -->
        </div>
    </div>

@endsection
