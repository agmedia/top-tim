@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/attribute.naslov') }}</h1>
            </div>
        </div>
    </div>


    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($attributes) ? route('attributes.update', ['attributes' => $attributes]) : route('attributes.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($attributes))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('attributes') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/attribute.povratak') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="faq-switch" name="status"{{ (isset($attributes->status) and $attributes->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="faq-switch">{{ __('back/attribute.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="title-input">{{ __('back/attribute.pitanje') }}</label>
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
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($attributes) && isset($attributes->translation($lang->code)->group_title) ? $attributes->translation($lang->code)->group_title : old('title.*') }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-group mb-4 d-none">
                                <label for="title-input">{{ __('back/attribute.tip') }}</label>
                                <select class="js-select2 form-control form-control" id="tip" name="type" style="width: 100%;" data-placeholder="Odaberite tip atributa...">
                                    <option></option>
                                   {{-- <option value="text" {{ (isset($attributes) and $attributes->type == 'text') ? 'selected' : '' }}>Tekstualni unos (input text)</option> --}}
                                    <option value="text" selected>Tekstualni unos (input text)</option>

                                </select>

                            </div>

                            <div id="addition">
                                @livewire('back.catalog.options-addition', ['values' => isset($attributes) ? $attributes : []])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/attribute.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            @if (isset($attributes))

                                <a href="{{ route('attributes.destroy', ['attributes' => $attributes]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="{{ __('back/attribute.obrisi') }}" onclick="event.preventDefault(); document.getElementById('delete-attribute-form{{ $attributes->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/attribute.obrisi') }}
                                </a>

                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if (isset($attributes))
            <form id="delete-attribute-form{{ $attributes->id }}" action="{{ route('attributes.destroy', ['attributes' => $attributes]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>


@endsection


