@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/option.naslov') }}</h1>
            </div>
        </div>
    </div>


    <div class="content content-full content-boxed">
        @include('back.layouts.partials.session')

        <form action="{{ isset($options) ? route('options.update', ['options' => $options]) : route('options.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if (isset($options))
                {{ method_field('PATCH') }}
            @endif
            <div class="block">
                <div class="block-header block-header-default">
                    <a class="btn btn-light" href="{{ route('options') }}">
                        <i class="fa fa-arrow-left mr-1"></i> {{ __('back/option.povratak') }}
                    </a>
                    <div class="block-options">
                        <div class="custom-control custom-switch custom-control-success">
                            <input type="checkbox" class="custom-control-input" id="faq-switch" name="status"{{ (isset($options->status) and $options->status) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="faq-switch">{{ __('back/option.aktiviraj') }}</label>
                        </div>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row justify-content-center push">
                        <div class="col-md-12">

                            <div class="form-group">
                                <label for="title-input">{{ __('back/option.pitanje') }}</label>
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
                                            <input type="text" class="form-control" id="title-input-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="{{ isset($options) ? $options->translation($lang->code)->title : old('title.*') }}" onkeyup="SetSEOPreview()">
                                        </div>
                                    @endforeach
                                </div>



                            </div>

                            <div class="form-group ">
                                <label for="title-input">{{ __('back/option.tip') }}</label>
                                <select class="js-select2 form-control form-control" id="tip" name="tip" style="width: 100%;" data-placeholder="Odaberite opciju">
                                    <option></option>
                                    <option value="1" selected>Boja</option>
                                    <option value="2">Veličina</option>
                                </select>

                            </div>
                            {{-- if tip == 2--}}
                            <div class="form-group  ">
                                <div class="block-header p-0 mb-2">
                                    <h3 class="block-title">{{ __('back/option.vrijednosti_atributa') }}</h3>
                                    <a class="btn btn-success btn-sm" href="">
                                        <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1">{{ __('back/option.dodaj_vrijednost') }}</span>
                                    </a>
                                </div>


                                <table class="table table-striped table-borderless table-vcenter">
                                    <thead class="thead-light">
                                    <tr>
                                        <th class="font-size-sm" style="width:35%"> <img src="{{ asset('media/flags/hr.png') }}" /></th>
                                        <th class="font-size-sm" style="width:35%"><img src="{{ asset('media/flags/en.png') }}" /></th>
                                        <th class="font-size-sm" style="width:10%">Color</th>

                                        <th class="font-size-sm" style="width:10%">{{ __('back/option.sort') }}</th>
                                        <th class="text-right font-size-sm"  style="width:auto">{{ __('back/option.uredi') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    <tr>

                                        <td>
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Crna" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Black" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="color" class="form-control form-control-sm" value="#000000" name="color1"></span>
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
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Crvena" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="text" class="form-control form-control-sm" value="Red" name="value"></span>
                                        </td>

                                        <td>
                                            <span class="font-size-sm"> <input type="color" class="form-control form-control-sm" value="#ff0000" name="color1"></span>
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
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-hero-success my-2">
                                <i class="fas fa-save mr-1"></i> {{ __('back/option.snimi') }}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            @if (isset($options))

                                <a href="{{ route('options.destroy', ['options' => $options]) }}" type="submit" class="btn btn-hero-danger my-2 js-tooltip-enabled" data-toggle="tooltip" title="" data-original-title="{{ __('back/option.obrisi') }}" onclick="event.preventDefault(); document.getElementById('delete-option-form{{ $options->id }}').submit();">
                                    <i class="fa fa-trash-alt"></i> {{ __('back/option.obrisi') }}
                                </a>

                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if (isset($options))
            <form id="delete-option-form{{ $options->id }}" action="{{ route('options.destroy', ['options' => $options]) }}" method="POST" style="display: none;">
                @csrf
                {{ method_field('DELETE') }}
            </form>
        @endif
    </div>


@endsection

@push('js_after')



@endpush
