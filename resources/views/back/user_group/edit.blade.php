@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">Korisnička grupa</h1>
            </div>
        </div>
    </div>


    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($user_groups) ? route('user_groups.update', ['user_groups' => $user_groups]) : route('user_groups.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($user_groups))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('user_groups') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/option.povratak') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="faq-switch" name="status"{{ (isset($user_groups->status) and $user_groups->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="faq-switch">{{ __('back/option.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="title-input">{{ __('back/user.naziv_grupe') }}</label>
                                <ul class="nav nav-pills float-right">
                                    @foreach(ag_lang() as $lang)
                                        <li @if ($lang->code == current_locale()) class="active" @endif>
                                            <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#title-{{ $lang->code }}">
                                                <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="tab-content">
                                    @foreach(ag_lang() as $lang)
                                        <div id="title-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($user_groups) ? $user_groups->translation($lang->code)->title : old('title.*') }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="group-select">{{ __('back/user.glavna_grupa') }}</label>
                                <select class="js-select2 form-control" id="group-select" name="parent_id" style="width: 100%;">
                                    @foreach ($groups['items'] as $user_group)
                                        <option value="0">{{ __('back/user.odaberi_glavnu_grupu') }}</option>
                                    @if(isset($user_groups->id) and $user_group['id'] != $user_groups->id)
                                        <option value="{{ $user_group['id'] }}">{{ $user_group['title'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/option.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            @if (isset($user_groups))

                                <a href="{{ route('user_groups.destroy', ['user_groups' => $user_groups]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="{{ __('back/option.obrisi') }}" onclick="event.preventDefault(); document.getElementById('delete-option-form{{ $user_groups->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/option.obrisi') }}
                                </a>

                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if (isset($user_groups))
            <form id="delete-option-form{{ $user_groups->id }}" action="{{ route('user_groups.destroy', ['user_groups' => $user_groups]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>


@endsection

@push('js_after')



@endpush
