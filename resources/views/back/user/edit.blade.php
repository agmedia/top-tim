@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/user.user_edit') }}</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('users') }}">{{ __('back/user.users') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('back/user.user_edit') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">

        @include('back.layouts.partials.session')
        <form action="{{ isset($user) ? route('users.update', ['user' => $user]) : route('users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($user))
                {{ method_field('PATCH') }}
            @endif

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('users') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/user.back') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="switch-status" name="status" {{ (isset($user->details->status) and $user->details->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="switch-status">{{ __('back/user.publish') }}</label>
                        </div>
                    </div>
                </div>

                <div class="block-content">
                    <h2 class="content-heading pt-0">
                        <i class="fa fa-fw fa-user-circle text-muted mr-1"></i> {{ __('back/user.user_profile') }}
                    </h2>
                    <div class="row push">
                        <div class="col-lg-4">
                            <p class="text-muted">
                                {{ __('back/user.user_profile_label') }}
                            </p>
                        </div>
                        <div class="col-lg-8 col-xl-5">
                            <div class="form-group">
                                <label for="input-username">{{ __('back/user.user_name') }}</label>
                                <input type="text" class="form-control" id="input-username" name="username" placeholder="" value="{{ isset($user) ? $user->name : old('username') }}">
                            </div>

                            <div class="form-group">
                                <label for="input-email">{{ __('back/user.email') }}</label>
                                <input type="email" class="form-control" id="input-email" name="email" placeholder="" value="{{ isset($user) ? $user->email : old('email') }}">
                            </div>

                            <div class="form-group">
                                <label for="input-phone">{{ __('back/user.user_phone') }}</label>
                                <input type="text" class="form-control" id="input-phone" name="phone" placeholder="" value="{{ isset($user->details->phone) ? $user->details->phone : old('phone') }}">
                            </div>
                        </div>
                    </div>

                    @if (auth()->user()->can('*'))
                        <h2 class="content-heading pt-0">
                            <i class="fa fa-fw fa-asterisk text-muted mr-1"></i> {{ __('back/user.promjena_lozinke') }}
                        </h2>
                        <div class="row push">
                            <div class="col-lg-4">
                                <p class="text-muted">
                                    {{ __('back/user.reset_lozinke') }}
                                </p>
                            </div>
                            <div class="col-lg-8 col-xl-5">
                                <div class="form-group">
                                    <label for="input-old-password">{{ __('back/user.trenutna_lozinka') }}</label>
                                    <input type="password" class="form-control" id="input-old-password" name="old_password">
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="dm-profile-edit-password-new">{{ __('back/user.nova_lozinka') }}</label>
                                        <input type="password" class="form-control" id="input-password" name="password">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="dm-profile-edit-password-new-confirm">{{ __('back/user.potvrdi_lozinku') }}</label>
                                        <input type="password" class="form-control" id="input-password-confirmation" name="password_confirmation">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <h2 class="content-heading pt-0">
                        <i class="fa fa-fw fa-user-circle text-muted mr-1"></i> {{ __('back/user.korisnicki_podaci') }}
                    </h2>
                    <div class="row push">
                        <div class="col-lg-4">
                            <p class="text-muted">{{ __('back/user.user_shipping_address_label') }}
                            </p>
                        </div>
                        <div class="col-lg-8 col-xl-5">
                            <div class="form-group row">
                                <div class="col-6">
                                    <label for="input-fname">{{ __('back/user.user_first_name') }}</label>
                                    <input type="text" class="form-control" id="input-fname" name="fname" value="{{ isset($user) ? $user->details->fname : old('fname') }}">
                                </div>
                                <div class="col-6">
                                    <label for="input-lname">{{ __('back/user.user_last_name') }}</label>
                                    <input type="text" class="form-control" id="input-lname" name="lname" value="{{ isset($user) ? $user->details->lname : old('lname') }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="input-address">{{ __('back/user.user_address') }}</label>
                                <input type="text" class="form-control" id="input-address" name="address" value="{{ isset($user) ? $user->details->address : old('address') }}">
                            </div>
                            <div class="form-group">
                                <label for="input-city">{{ __('back/user.user_city') }}</label>
                                <input type="text" class="form-control" id="input-city" name="city" value="{{ isset($user) ? $user->details->city : old('city') }}">
                            </div>
                            <div class="form-group">
                                <label for="input-zip">{{ __('back/user.user_zip') }}</label>
                                <input type="text" class="form-control" id="input-zip" maxlength="5" name="zip" value="{{ isset($user) ? $user->details->zip : old('zip') }}">
                            </div>
                            <div class="form-group">
                                <label for="input-state">{{ __('back/user.user_country') }}</label>
                                <input type="text" class="form-control" id="input-state" name="state" value="{{ isset($user) ? $user->details->state : old('state') }}">
                            </div>

                            <div class="form-group">
                                <label for="input-loyalty">{{ __('back/user.user_loyalty') }}</label>
                                <input type="text" class="form-control" id="input-loyalty" name="loyalty_points" value="{{ isset($points) ? $points : old('state') }}">
                            </div>
                        </div>
                    </div>

                    <h2 class="content-heading pt-0">
                        <i class="fa fa-fw fa-user-circle text-muted mr-1"></i> {{ __('back/user.user_role_change') }}
                    </h2>
                    <div class="row push">
                        <div class="col-lg-4">
                            <p class="text-muted">{{ __('back/user.user_role_label') }}
                            </p>
                        </div>
                        <div class="col-lg-8 col-xl-5">
                            <div class="form-group row">
                                <label for="price-input">{{ __('back/user.user_role') }}</label>
                                <select class="js-select2 form-control" id="role-select" name="role" style="width: 100%;" data-placeholder="{{ __('back/user.user_role_select') }}">
                                    <option></option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}" {{ ((isset($user)) and ($user->details->role == $role->name)) ? 'selected' : '' }}>{{ $role->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/user.save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="user_id" value="{{ isset($user) ? $user->id : old('user_id') }}">
        </form>
    </div>

@endsection

@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(() => {
            $('#role-select').select2({
                minimumResultsForSearch: Infinity
            });
        });
    </script>
@endpush
