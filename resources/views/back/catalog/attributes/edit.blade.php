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
                        <div class="col-md-10">

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
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($attributes) ? $attributes->translation($lang->code)->title : old('title.*') }}" onkeyup="SetSEOPreview()">
                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group mb-4">
                                <label for="title-input">{{ __('back/attribute.tip') }}</label>
                                <select class="js-select2 form-control form-control" id="tip" name="tip" style="width: 100%;" data-placeholder="Odaberite opciju">
                                    <option></option>
                                    <option value="1" selected>Odabir (Select)</option>
                                    <option value="2">Tekstualni unos (input text)</option>
                                </select>

                            </div>
                            {{-- if tip == 2--}}
                            <div class="form-group  ">
                                <div class="block-header p-0 mb-2">
                                    <h3 class="block-title">{{ __('back/attribute.vrijednosti_atributa') }}</h3>
                                    <a class="btn btn-success btn-sm" href="">
                                        <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">{{ __('back/attribute.dodaj_vrijednost') }}</span>
                                    </a>
                                </div>


                                <table class="table table-striped table-borderless table-vcenter">
                                    <thead class="thead-light">
                                    <tr>
                                        <th class="font-size-sm" style="width:35%"> <img src="{{ asset('media/flags/hr.png') }}" /></th>
                                        <th class="font-size-sm" style="width:35%"><img src="{{ asset('media/flags/en.png') }}" /></th>
                                        <th class="font-size-sm" style="width:10%">{{ __('back/attribute.sort') }}</th>
                                        <th class="text-right font-size-sm"  style="width:20%">{{ __('back/attribute.uredi') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    <tr>

                                        <td>
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Muški" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Man" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="number" class="form-control form-control-sm" value="1" name="qty"></span>
                                        </td>
                                        <td class="text-right font-size-sm">
                                            <button type="button" class="btn btn-sm btn-alt-success"><i class="fa fa-save"></i></button>

                                            <button onclick="event.preventDefault();" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></button>
                                        </td>
                                    </tr>

                                    <tr>

                                        <td>
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Ženski" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Woman" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="number" class="form-control form-control-sm" value="2" name="qty"></span>
                                        </td>
                                        <td class="text-right font-size-sm">
                                            <button type="button" class="btn btn-sm btn-alt-success"><i class="fa fa-save"></i></button>

                                            <button onclick="event.preventDefault();" class="btn btn-sm btn-alt-danger"><i class="fa fa-fw fa-trash-alt"></i></button>
                                        </td>
                                    </tr>






                                    </tbody>
                                </table>







                            </div>
                            {{-- end if--}}
                        </div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <div class="row justify-content-center push">
                        <div class="col-md-5">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/attribute.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-5 text-right">
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
