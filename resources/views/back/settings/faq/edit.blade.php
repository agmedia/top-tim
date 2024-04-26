@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">FAQ edit</h1>
            </div>
        </div>
    </div>

    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($faq) ? route('faqs.update', ['faq' => $faq]) : route('faqs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($faq))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('faqs') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/faq.povratak') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="faq-switch" name="status"{{ (isset($faq->status) and $faq->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="faq-switch">{{ __('back/faq.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-10">

                            <div class="form-group">
                                <label for="title-input">{{ __('back/faq.pitanje') }}</label>
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
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($faq) ? $faq->translation($lang->code)->title : old('title.*') }}" onkeyup="SetSEOPreview()">
                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group row  mb-4">
                                <div class="col-md-12">
                                    <label for="description-editor">{{ __('back/faq.odgovor') }}</label>

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
                                                <textarea id="description-editor-{{ $lang->code }}" name="description[{{ $lang->code }}]" placeholder="{{ $lang->code }}">{!! isset($faq) ? $faq->translation($lang->code)->description : old('description.*') !!}</textarea>
                                            </div>
                                        @endforeach
                                    </div>


                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-5">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/faq.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-5 text-right">
                        @if (isset($faq))

                                <a href="{{ route('faqs.destroy', ['faq' => $faq]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="{{ __('back/faq.obrisi') }}" onclick="event.preventDefault(); document.getElementById('delete-faq-form{{ $faq->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/faq.obrisi') }}
                                </a>

                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if (isset($faq))
            <form id="delete-faq-form{{ $faq->id }}" action="{{ route('faqs.destroy', ['faq' => $faq]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>
@endsection

@push('js_after')
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


@endpush
