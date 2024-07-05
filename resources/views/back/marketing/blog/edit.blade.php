@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/flatpickr/flatpickr.min.css') }}">
@endpush

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/blog.blog_edit') }}</h1>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($blog) ? route('blogs.update', ['blog' => $blog]) : route('blogs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($blog))
                {{ method_field('PATCH') }}
            @endif

            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('blogs') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/blog.povratak') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="dm-post-edit-active" name="status" {{ (isset($blog) and $blog->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="dm-post-edit-active">{{ __('back/blog.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-10">

                            <div class="form-group">
                                <label for="title-input">{{ __('back/blog.naslov') }}</label>

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
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($blog) ? $blog->translation($lang->code)->title : old('title.*') }}" onkeyup="SetSEOPreview()">
                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group">
                                <label for="short-description-input">{{ __('back/blog.sazetak') }}</label>

                                <ul class="nav nav-pills float-right">
                                    @foreach(ag_lang() as $lang)
                                        <li @if ($lang->code == current_locale()) class="active" @endif>
                                            <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#short-description-{{ $lang->code }}">
                                                <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content">
                                    @foreach(ag_lang() as $lang)
                                        <div id="short-description-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                            <textarea class="form-control" id="short-description-input-{{ $lang->code }}" name="short_description[{{ $lang->code }}]" rows="3" placeholder="{{ $lang->code }}">{{ isset($blog) ? $blog->translation($lang->code)->short_description : old('short_description.*') }}</textarea>
                                            <div class="form-text text-muted font-size-sm font-italic">{{ __('back/blog.vidljivo_na_pocetnoj_stranici') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>




                            <div class="form-group row">
                                <div class="col-xl-6">
                                    <label>{{ __('back/blog.glavna_slika') }}</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="image-input" name="image" data-toggle="custom-file-input" onchange="readURL(this);">
                                        <label class="custom-file-label" for="image-input">{{ __('back/blog.odaberite_sliku') }}</label>
                                    </div>
                                    <div class="mt-2">
                                        <img class="img-fluid" id="image-view" src="{{ isset($blog) ? asset($blog->image) : asset('media/img/lightslider.webp') }}" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row  mb-4">
                                <div class="col-md-12">
                                    <label for="description-editor">{{ __('back/blog.opis') }}</label>

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
                                                <textarea id="description-editor-{{ $lang->code }}" name="description[{{ $lang->code }}]" placeholder="{{ $lang->code }}">{!! isset($blog) ? $blog->translation($lang->code)->description : old('description.*') !!}</textarea>
                                            </div>
                                        @endforeach
                                    </div>



                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-xl-6">
                                    <label for="publish-date-input">{{ __('back/blog.datum_objave') }}</label>
                                    <input type="text" class="js-flatpickr form-control bg-white" id="publish-date-input"
                                           value="{{ isset($blog) && $blog->publish_date ? \Illuminate\Support\Carbon::make($blog->publish_date)->format('d.m.Y') : '' }}"
                                           name="publish_date" data-enable-time="true" placeholder="{{ __('back/blog.ili_ostavi_prazno') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">{{ __('back/blog.meta_data_seo') }}</h3>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center">
                        <div class="col-md-10 ">
                            <form action="be_pages_ecom_product_edit.html" method="POST" onsubmit="return false;">
                                <div class="form-group">
                                    <label for="meta-title-input">{{ __('back/blog.meta_naslov') }}</label>

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


                                                <input type="text" class="js-maxlength form-control" id="meta-title-input-{{ $lang->code }}" name="meta_title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($blog) ? $blog->translation($lang->code)->meta_title : old('meta_title.*') }}" maxlength="70" data-always-show="true" data-placement="top">


                                                <small class="form-text text-muted">
                                                    {{ __('back/blog.70_znakova_max') }}
                                                </small>
                                            </div>
                                        @endforeach
                                    </div>



                                </div>

                                <div class="form-group">
                                    <label for="meta-description-input">{{ __('back/blog.meta_opis') }}</label>

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

                                                <textarea class="js-maxlength form-control" id="meta-description-input-{{ $lang->code }}" name="meta_description[{{ $lang->code }}]" placeholder="{{ $lang->code }}" rows="4" maxlength="160" data-always-show="true" data-placement="top">{{ isset($blog) ? $blog->translation($lang->code)->meta_description : old('meta_description.*') }}</textarea>
                                                <small class="form-text text-muted">
                                                    {{ __('back/blog.160_znakova_max') }}
                                                </small>
                                            </div>
                                        @endforeach
                                    </div>



                                </div>

                                <div class="form-group">
                                    <label for="slug-input">{{ __('back/blog.seo_url') }}</label>

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


                                                <input type="text" class="form-control" id="slug-input-{{ $lang->code }}" placeholder="{{ $lang->code }}" value="{{ isset($blog) ? $blog->translation($lang->code)->slug : old('slug.*') }}" disabled>

                                                <input type="hidden" name="slug[{{ $lang->code }}]" value="{{ isset($blog) ? $blog->translation($lang->code)->slug : old('slug.*') }}">

                                            </div>
                                        @endforeach
                                    </div>




                                </div>

                            </form>
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-5">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/blog.snimi') }}
                            </button>
                        </div>

                            <div class="col-md-5 text-right">
                                @if (isset($blog))
                                <a href="{{ route('blogs.destroy', ['blog' => $blog]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="Obriši" onclick="event.preventDefault(); document.getElementById('delete-blog-form{{ $blog->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/blog.obrisi') }}
                                </a>
                                @endif
                            </div>

                    </div>
                </div>
            </div>
        </form>

        @if (isset($blog))
            <form id="delete-blog-form{{ $blog->id }}" action="{{ route('blogs.destroy', ['blog' => $blog]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('js_after')
    <script src="{{ asset('js/plugins/ckeditor5-classic/build/ckeditor.js') }}"></script>
    <script src="{{ asset('js/plugins/flatpickr/flatpickr.min.js') }}"></script>

    <!-- Page JS Helpers (CKEditor 5 plugins) -->
    <script>jQuery(function(){Dashmix.helpers(['flatpickr']);});</script>

    <script>
        $(() => {
            {!! ag_lang() !!}.forEach(function(item) {
                ClassicEditor
                .create(document.querySelector('#description-editor-' + item.code), {
                    ckfinder: {
                        uploadUrl: '{{ route('blogs.upload.image') }}?_token=' + document.querySelector('meta[name="csrf-token"]').getAttribute('content') + '&blog_id={{ (isset($blog->id) && $blog->id) ?: 0 }}',
                    }
                })
                .then( editor => {
                    console.log(editor);
                } )
                .catch( error => {
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
