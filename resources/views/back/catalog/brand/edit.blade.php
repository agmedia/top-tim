@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/brands.brand_edit') }}</h1>
                <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('brands') }}">{{ __('back/brands.brands') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('back/brands.novi_brand') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="content content-full content-boxed">

        <!-- END Page Content -->
    @include('back.layouts.partials.session')
    <!-- New Post -->
        <form action="{{ isset($brand) ? route('brands.update', ['brand' => $brand]) : route('brands.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($brand))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('brands') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/brands.povratak') }}
                    </a>
                    <div class="block-options d-inline-block">
                        <div class="custom-control custom-switch custom-control-success d-inline-block mr-5">
                            <input type="checkbox" class="custom-control-input" id="featured-switch" name="featured"{{ (isset($brand->featured) and $brand->featured) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="featured-switch">{{ __('back/brands.izdvojeni_brand') }}</label>
                        </div>
                        <div class="custom-control custom-switch custom-control-success d-inline-block">
                            <input type="checkbox" class="custom-control-input" id="brand-switch" name="status"{{ (isset($brand->status) and $brand->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="brand-switch">{{ __('back/brands.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-10">

                            <div class="form-group">
                                <label for="title-input">{{ __('back/brands.naslov') }}</label>

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
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($brand) ? $brand->translation($lang->code)->title : old('title.*') }}" onkeyup="SetSEOPreview()">
                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group">
                                <label for="slug-input">{{ __('back/brands.seo_url') }}</label>
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


                                            <input type="text" class="form-control" id="slug-input-{{ $lang->code }}" placeholder="{{ $lang->code }}" value="{{ isset($brand) ? $brand->translation($lang->code)->slug : old('slug.*') }}" disabled>

                                            <input type="hidden" name="slug[{{ $lang->code }}]" value="{{ isset($brand) ? $brand->translation($lang->code)->slug : old('slug.*') }}">

                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group">
                                <label for="description-editor">{{ __('back/brands.opis') }}</label>

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
                                            <textarea id="description-editor-{{ $lang->code }}" name="description[{{ $lang->code }}]" placeholder="{{ $lang->code }}">{!! isset($brand) ? $brand->translation($lang->code)->description : old('description.*') !!}</textarea>
                                        </div>
                                    @endforeach
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


                                            <input type="text" class="js-maxlength form-control" id="meta-title-input-{{ $lang->code }}" name="meta_title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($brand) ? $brand->translation($lang->code)->meta_title : old('meta_title.*') }}" maxlength="70" data-always-show="true" data-placement="top">


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

                                            <textarea class="js-maxlength form-control" id="meta-description-input-{{ $lang->code }}" name="meta_description[{{ $lang->code }}]" placeholder="{{ $lang->code }}" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ isset($brand) ? $brand->translation($lang->code)->meta_description : old('meta_description.*') }}</textarea>
                                            <small class="form-text text-muted">
                                                {{ __('back/categories.160_znakova_max') }}
                                            </small>
                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group row">
                                <div class="col-xl-6">
                                    <label>{{ __('back/categories.open_graph_slika') }}</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image-input" name="image" data-toggle="custom-file-input" onchange="readURL(this);">
                                        <label class="custom-file-label" for="image-input">{{ __('back/categories.odaberite_sliku') }}</label>
                                    </div>
                                    <div class="mt-2">
                                        <img class="img-fluid" id="image-view" src="{{ isset($brand) ? asset($brand->image) : asset('media/img/lightslider.webp') }}" alt="">
                                    </div>
                                    <div class="form-text text-muted font-size-sm font-italic">{{ __('back/categories.slika_koja_se_pokazuje') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-5">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/categories.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-5 text-right">
                        @if (isset($brand))

                                <a href="{{ route('brands.destroy', ['brand' => $brand]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši" onclick="event.preventDefault(); document.getElementById('delete-brand-form{{ $brand->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i>  {{ __('back/categories.obrisi') }}
                                </a>

                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <!-- END New Post -->
        @if (isset($brand))
            <form id="delete-brand-form{{ $brand->id }}" action="{{ route('brands.destroy', ['brand' => $brand]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif

    </div>

@endsection

@push('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/ckeditor5-classic/build/ckeditor.js') }}"></script>

    <script>
        $(() => {
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
