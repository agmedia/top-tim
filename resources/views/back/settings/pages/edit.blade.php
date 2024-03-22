@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">

    <style>
        .cke_skin_kama .cke_button_CMDSuperButton .cke_label {
            display: inline;
        }
    </style>

@endpush

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/info.info_stranica_edit') }}</h1>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($page) ? route('pages.update', ['page' => $page]) : route('pages.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($page))
                {{ method_field('PATCH') }}
            @endif

            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ back()->getTargetUrl() }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/info.povratak') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="status-switch" name="status" {{ (isset($page) and $page->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="status-switch">{{ __('back/info.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-10">

                            <div class="form-group">
                                <label for="title-input">{{ __('back/info.naslov') }}</label>

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
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($page) ? $page->translation($lang->code)->title : old('title.*') }}" onkeyup="SetSEOPreview()">
                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group">
                                <label for="group-select">{{ __('back/info.grupa') }}</label>
                                <select class="js-select2 form-control" id="group-select" name="group" style="width: 100%;">
                                    @foreach ($groups as $group)
                                        <option value="{{ $group }}" {{ ((isset($page)) and ($page->subgroup == $group)) ? 'selected' : '' }}>{{ $group }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group row d-none">
                                <div class="col-xl-6">
                                    <label>{{ __('back/info.glavna_slika') }}</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image-input" name="image" data-toggle="custom-file-input" onchange="readURL(this);">
                                        <label class="custom-file-label" for="image-input">{{ __('back/info.odaberite_sliku') }}</label>
                                    </div>
                                    <div class="mt-2">
                                        <img class="img-fluid" id="image-view" src="{{ isset($page) ? asset($page->image) : asset('media/img/lightslider.webp') }}" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row  mb-4">
                                <div class="col-md-12">
                                    <label for="description-editor">{{ __('back/info.opis') }}</label>
                                    <ul class="nav nav-pills float-right">
                                        @foreach(ag_lang() as $lang)
                                            <li @if ($lang->code == current_locale()) class="active" @endif>
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#description-{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>


                                    <div class="tab-content">
                                        @foreach(ag_lang() as $lang)
                                            <div id="description-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                <textarea id="description-editor-{{ $lang->code }}" name="description[{{ $lang->code }}]" placeholder="{{ $lang->code }}">{!! isset($page) ? $page->translation($lang->code)->description : old('description.*') !!}</textarea>
                                            </div>
                                            <input type="hidden" name="short_description[{{ $lang->code }}]" value="">
                                        @endforeach
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">{{ __('back/categories.meta_data_seo') }}</h3>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center">
                        <div class="col-md-10 ">
                            <form action="be_pages_ecom_product_edit.html" method="POST" onsubmit="return false;">
                                <div class="form-group">
                                    <label for="meta-title-input">{{ __('back/categories.meta_naslov') }}</label>

                                    <ul class="nav nav-pills float-right">
                                        @foreach(ag_lang() as $lang)
                                            <li @if ($lang->code == current_locale()) class="active" @endif>
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#meta_title-{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="tab-content">
                                        @foreach(ag_lang() as $lang)
                                            <div id="meta_title-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">


                                                <input type="text" class="js-maxlength form-control" id="meta-title-input-{{ $lang->code }}" name="meta_title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($page) ? $page->translation($lang->code)->meta_title : old('meta_title.*') }}" maxlength="70" data-always-show="true" data-placement="top">


                                                <small class="form-text text-muted">
                                                    {{ __('back/categories.70_znakova_max') }}
                                                </small>
                                            </div>
                                        @endforeach
                                    </div>



                                </div>

                                <div class="form-group">
                                    <label for="meta-description-input">{{ __('back/categories.meta_opis') }}</label>

                                    <ul class="nav nav-pills float-right">
                                        @foreach(ag_lang() as $lang)
                                            <li @if ($lang->code == current_locale()) class="active" @endif>
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#meta-description-{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="tab-content">
                                        @foreach(ag_lang() as $lang)
                                            <div id="meta-description-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">

                                                <textarea class="js-maxlength form-control" id="meta-description-input-{{ $lang->code }}" name="meta_description[{{ $lang->code }}]" placeholder="{{ $lang->code }}" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ isset($page) ? $page->translation($lang->code)->meta_description : old('meta_description.*') }}</textarea>
                                                <small class="form-text text-muted">
                                                    {{ __('back/categories.160_znakova_max') }}
                                                </small>
                                            </div>
                                        @endforeach
                                    </div>


                                </div>

                                <div class="form-group">
                                    <label for="slug-input"> {{ __('back/categories.seo_url') }}</label>

                                    <ul class="nav nav-pills float-right">
                                        @foreach(ag_lang() as $lang)
                                            <li @if ($lang->code == current_locale()) class="active" @endif>
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#slug-input-{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>


                                    <div class="tab-content">
                                        @foreach(ag_lang() as $lang)
                                            <div id="slug-input-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">


                                                <input type="text" class="form-control" id="slug-input-{{ $lang->code }}" placeholder="{{ $lang->code }}" value="{{ isset($page) ? $page->translation($lang->code)->slug : old('slug.*') }}" disabled>

                                                <input type="hidden" name="slug[{{ $lang->code }}]" value="{{ isset($page) ? $page->translation($lang->code)->slug : old('slug.*') }}">

                                            </div>
                                        @endforeach
                                    </div>



                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/categories.snimi') }}
                            </button>
                        </div>
                        @if (isset($page) && $page->subgroup != '/')
                            <div class="col-md-6 text-right">
                                <a href="{{ route('pages.destroy', ['page' => $page]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši" onclick="event.preventDefault(); document.getElementById('delete-page-form{{ $page->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/categories.obrisi') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        @if (isset($page))
            <form id="delete-page-form{{ $page->id }}" action="{{ route('pages.destroy', ['page' => $page]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/ckeditor5-classic/build/ckeditor.js') }}"></script>

    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>

    <!-- Page JS Helpers (CKEditor 5 plugins) -->
    <script>jQuery(function(){Dashmix.helpers(['flatpickr']);});</script>


    <script>
        $(() => {
            $('#group-select').select2({
                placeholder: '{{ __('back/info.odaberite_ili_upisite_novu_grupu') }}',
                tags: true
            });

            {!! ag_lang() !!}.forEach(function(item) {
                ClassicEditor
                    .create(document.querySelector('#description-editor-' + item.code ))

                    .then(editor => {
                        console.log(editor);
                    })
                    .catch(error => {
                        console.error(error);
                    });

            });


        })
    </script>



    <script>
        function SetSEOPreview() {
            let title = $('#title-input').val();
            $('#slug-input').val(slugify(title));
        }

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#image-view')
                    .attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

@endpush
