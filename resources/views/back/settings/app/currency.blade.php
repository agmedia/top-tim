@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/app.currency.title') }}</h1>
                <button class="btn btn-hero-secondary my-2 mr-2" onclick="event.preventDefault(); openMainModal();">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/app.currency.select_main') }}</span>
                </button>
                <button class="btn btn-hero-success my-2" onclick="event.preventDefault(); openModal();">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/app.currency.new') }}</span>
                </button>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @include('back.layouts.partials.session')

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('back/app.currency.list') }}</h3>
            </div>
            <div class="block-content">
                <table class="table table-striped table-borderless table-vcenter">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 5%;">{{ __('back/app.currency.br') }}</th>
                        <th style="width: 60%;">{{ __('back/app.currency.input_title') }}</th>
                        <th class="text-center">{{ __('back/app.currency.code') }}</th>
                        <th class="text-center">{{ __('back/app.currency.status_title') }}</th>
                        <th class="text-right">{{ __('back/app.currency.edit_title') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ isset($item->title->{current_locale()}) ? $item->title->{current_locale()} : $item->title }}
                                @if (isset($item->main) && $item->main)
                                    <strong><small>&nbsp;({{ __('back/app.currency.default_currency') }})</small></strong>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->code }}</td>
                            <td class="text-center">@include('back.layouts.partials.status', ['status' => $item->status])</td>
                            <td class="text-right font-size-sm">
                                <button class="btn btn-sm btn-alt-secondary" onclick="event.preventDefault(); openModal({{ json_encode($item) }});">
                                    <i class="fa fa-fw fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-alt-danger" onclick="event.preventDefault(); deleteCurrency({{ $item->id }});">
                                    <i class="fa fa-fw fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="5">{{ __('back/app.currency.empty_list') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="currency-modal" tabindex="-1" role="dialog" aria-labelledby="currency-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-popout" role="document">
            <div class="modal-content rounded">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary">
                        <h3 class="block-title">{{ __('back/app.currency.edit_title') }}</h3>
                        <div class="block-options">
                            <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row justify-content-center mb-3">
                            <div class="col-md-10">
                                <div class="form-group mb-4">
                                    <label for="currency-title" class="w-100">{{ __('back/app.currency.input_title') }}
                                        <ul class="nav nav-pills float-right">
                                            @foreach(ag_lang() as $lang)
                                                <li @if ($lang->code == current_locale()) class="active" @endif ">
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if ($lang->code == current_locale()) active @endif " data-toggle="pill" href="#title-{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                                </li>
                                            @endforeach
                                        </ul>

                                    </label>
                                    <div class="tab-content">
                                        @foreach(ag_lang() as $lang)
                                            <div id="title-{{ $lang->code }}" class="tab-pane @if ($lang->code == current_locale()) active @endif">
                                                <input type="text" class="form-control" id="currency-title-{{ $lang->code }}" name="currency-title[{{ $lang->code }}]" placeholder="{{ $lang->code }}"  >
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="currency-code">{{ __('back/app.currency.code') }}</label>
                                    <input type="text" class="form-control" id="currency-code" name="code">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency-symbol-left">{{ __('back/app.currency.symbol_left') }}</label>
                                            <input type="text" class="form-control" id="currency-symbol-left" name="symbol_left">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency-symbol-right">{{ __('back/app.currency.symbol_right') }}</label>
                                            <input type="text" class="form-control" id="currency-symbol-right" name="symbol_right">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency-value">{{ __('back/app.currency.value') }}</label>
                                            <input type="text" class="form-control" id="currency-value" name="value">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency-decimal-places">{{ __('back/app.currency.decimal') }}</label>
                                            <input type="text" class="form-control" id="currency-decimal-places" name="decimal_places">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="css-control css-control-sm css-control-success css-switch res">
                                        <input type="checkbox" class="css-control-input" id="currency-status" name="status">
                                        <span class="css-control-indicator"></span> {{ __('back/app.currency.status_title') }}
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label class="css-control css-control-sm css-control-success css-switch res">
                                        <input type="checkbox" class="css-control-input" id="currency-main" name="main">
                                        <span class="css-control-indicator"></span> {{ __('back/app.currency.default_currency') }}
                                    </label>
                                </div>

                                <input type="hidden" id="currency-id" name="id" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                            {{ __('back/app.currency.cancel') }} <i class="fa fa-times ml-2"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); createCurrency();">
                            {{ __('back/app.currency.save') }} <i class="fa fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="main-currency-modal" tabindex="-1" role="dialog" aria-labelledby="main-currency-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-popout" role="document">
            <div class="modal-content rounded">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary">
                        <h3 class="block-title"> {{ __('back/app.currency.select_main') }}</h3>
                        <div class="block-options">
                            <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row justify-content-center mb-3">
                            <div class="col-md-10 mt-3">
                                <div class="form-group">
                                    <select class="js-select2 form-control" id="currency-main-select" name="currency_main_select" style="width: 100%;" data-placeholder="{{ __('back/app.currency.select_main') }}">
                                        <option></option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}" {{ ((isset($main)) and ($main->id == $item->id)) ? 'selected' : '' }}>
                                                {{ isset($item->title->{current_locale()}) ? $item->title->{current_locale()} : $item->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
<!--                                <div class="form-group">
                                    <div class="custom-control custom-switch custom-control-info">
                                        <input type="checkbox" class="custom-control-input" id="change-prices-switch" name="change_prices">
                                        <label class="custom-control-label" for="change-prices-switch">Preračunaj Cijene</label>
                                    </div>
                                </div>-->
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                            {{ __('back/app.currency.cancel') }} <i class="fa fa-times ml-2"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); storeMainCurrency();">
                            {{ __('back/app.currency.save') }} <i class="fa fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete-currency-modal" tabindex="-1" role="dialog" aria-labelledby="currency-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-popout" role="document">
            <div class="modal-content rounded">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary">
                        <h3 class="block-title">{{ __('back/app.currency.delete_tax') }}</h3>
                        <div class="block-options">
                            <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row justify-content-center mb-3">
                            <div class="col-md-10">
                                <h4>{{ __('back/app.currency.delete_shure') }}</h4>
                                <input type="hidden" id="delete-currency-id" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                            {{ __('back/app.currency.cancel') }}  <i class="fa fa-times ml-2"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="event.preventDefault(); confirmDelete();">
                            {{ __('back/app.currency.delete') }}  <i class="fa fa-trash-alt ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('js_after')
    <script src="{{ asset('js/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(() => {
            $('#currency-main-select').select2({
                minimumResultsForSearch: Infinity
            });
        });
        /**
         *
         * @param item
         * @param type
         */
        function openModal(item = {}) {
            $('#currency-modal').modal('show');
            editCurrency(item);
        }

        /**
         *
         * @param item
         * @param type
         */
        function openMainModal(item = {}) {
            $('#main-currency-modal').modal('show');
        }

        /**
         *
         */
        function createCurrency() {
            let titles = {};

            {!! ag_lang() !!}.forEach(function(lang) {
                titles[lang.code] = document.getElementById('currency-title-' + lang.code).value;
            });

            let item = {
                id: $('#currency-id').val(),
                title: titles,
                code: $('#currency-code').val(),
                symbol_left: $('#currency-symbol-left').val(),
                symbol_right: $('#currency-symbol-right').val(),
                value: $('#currency-value').val(),
                decimal_places: $('#currency-decimal-places').val(),
                status: $('#currency-status')[0].checked,
                main: $('#currency-main')[0].checked,
            };

            axios.post("{{ route('api.currencies.store') }}", { data: item })
            .then(response => {
                if (response.data.success) {
                    location.reload();
                } else {
                    return errorToast.fire(response.data.message);
                }
            });
        }

        /**
         *
         */
        function storeMainCurrency() {
            let item = {
                main: $('#currency-main-select').val()
            };

            axios.post("{{ route('api.currencies.store.main') }}", { data: item })
            .then(response => {
                console.log(response.data)
                if (response.data.success) {
                    location.reload();
                } else {
                    return errorToast.fire(response.data.message);
                }
            });
        }

        /**
         *
         */
        function deleteCurrency(id) {
            $('#delete-currency-modal').modal('show');
            $('#delete-currency-id').val(id);
        }

        /**
         *
         */
        function confirmDelete() {
            let item = { id: $('#delete-currency-id').val() };

            axios.post("{{ route('api.currencies.destroy') }}", { data: item })
            .then(response => {
                if (response.data.success) {
                    location.reload();
                } else {
                    return errorToast.fire(response.data.message);
                }
            });
        }

        /**
         *
         * @param item
         */
        function editCurrency(item) {
            $('#currency-id').val(item.id);
            $('#currency-code').val(item.code);
            $('#currency-symbol-left').val(item.symbol_left);
            $('#currency-symbol-right').val(item.symbol_right);
            $('#currency-value').val(item.value);
            $('#currency-decimal-places').val(item.decimal_places);

            {!! ag_lang() !!}.forEach((lang) => {
                if (item.title && typeof item.title[lang.code] !== undefined) {
                    $('#currency-title-' + lang.code).val(item.title[lang.code]);
                }
            });

            if (item.status) {
                $('#currency-status')[0].checked = item.status ? true : false;
            }

            if (item.main) {
                $('#currency-main')[0].checked = item.main ? true : false;
            }
        }
    </script>
@endpush
