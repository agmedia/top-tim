@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/app.geozone.main_title') }}</h1>

                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('geozones') }}">{{ __('back/app.geozone.title') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('back/app.geozone.main_title') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($geo_zone) ? route('geozones.update', ['geozone' => $geo_zone->id]) : route('geozones.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($geo_zone))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ back()->getTargetUrl() }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/app.geozone.back') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="geozone-switch" name="status"{{ (isset($geo_zone->status) and $geo_zone->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="geozone-switch">Status</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-10">

                            <div class="form-group mb-4">
                                <label for="language-title" class="w-100">{{ __('back/app.geozone.input_title') }} <span class="text-danger">*</span>
                                    <ul class="nav nav-pills float-right">
                                        @foreach(ag_lang() as $lang)
                                            <li @if (current_locale() == $lang->code) class="active" @endif>
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if (current_locale() == $lang->code) active @endif " data-toggle="pill" href="#{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </label>
                                <div class="tab-content">
                                    @foreach(ag_lang() as $lang)
                                        <div id="{{ $lang->code }}" class="tab-pane @if (current_locale() == $lang->code) active @endif">
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($geo_zone->title->{$lang->code}) ? $geo_zone->title->{$lang->code} : old('title') }}">
                                            @error('title.*')
                                            <span class="text-danger font-italic">Greška. Niste unijeli naslov.</span>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="language-title" class="w-100">{{ __('back/app.geozone.description') }}<span class="small text-gray"> ({{ __('back/app.geozone.description_if_needed') }})</span>
                                    <ul class="nav nav-pills float-right">
                                        @foreach(ag_lang() as $lang)
                                            <li @if (current_locale() == $lang->code) class="active" @endif>
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if (current_locale() == $lang->code) active @endif " data-toggle="pill" href="#description-{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </label>
                                <div class="tab-content">
                                    @foreach(ag_lang() as $lang)
                                        <div id="description-{{ $lang->code }}" class="tab-pane @if (current_locale() == $lang->code) active @endif">
                                            <textarea class="form-control" id="description-input-{{ $lang->code }}" name="description[{{ $lang->code }}]" rows="4">{{ isset($geo_zone->description->{$lang->code}) ? $geo_zone->description->{$lang->code} : old('description') }}</textarea>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @livewire('back.settings.states-addition', ['states' => isset($geo_zone) ? $geo_zone->state : []])

                            <input type="hidden" name="id" value="{{ isset($geo_zone) ? $geo_zone->id : 0 }}">

                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-10">
                            <button type="submit" class="btn btn-hero-success">
                                <i class="fas fa-save mr-1"></i> {{ __('back/layout.btn.save') }}
                            </button>
                            @if (isset($geo_zone))
                                <a href="{{ route('geozones.destroy', ['geozone' => $geo_zone->id]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled float-right" data-toggle="tooltip" title="" data-original-title="{{ __('back/layout.btn.delete') }}" onclick="event.preventDefault(); document.getElementById('delete-geozone-form{{ $geo_zone->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/layout.btn.delete') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if (isset($geo_zone))
            <form id="delete-geozone-form{{ $geo_zone->id }}" action="{{ route('geozones.destroy', ['geozone' => $geo_zone->id]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('js/plugins/ckeditor5-classic/build/ckeditor.js') }}"></script>

    <script>
        $(() => {
            $('#countries-select').select2({
                placeholder: "{{ __('back/app.geozone.select_country') }}"
            });
        });

        function addState() {
            let selected = $('#countries-select').val();

            console.log(selected);
        }
    </script>

@endpush
