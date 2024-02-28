@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">{{ __('back/app.statuses.title') }}</h1>
                <button class="btn btn-hero-success my-2" onclick="event.preventDefault(); openModal();">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> {{ __('back/app.statuses.new') }}</span>
                </button>
            </div>
        </div>
    </div>

    <div class="content content-full">
    @include('back.layouts.partials.session')

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('back/app.statuses.list') }}</h3>
            </div>
            <div class="block-content">
                <table class="table table-striped table-borderless table-vcenter">
                    <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="width: 5%;">{{ __('back/app.statuses.br') }}</th>
                        <th class="text-center" style="width: 7%;">ID</th>
                        <th style="width: 50%;">{{ __('back/app.statuses.input_title') }}</th>
                        <th class="text-center">{{ __('back/app.statuses.color') }}</th>
                        <th class="text-center">{{ __('back/app.statuses.sort_order') }}</th>
                        <th class="text-right">{{ __('back/app.statuses.edit_title') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($statuses as $status)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}.</td>
                            <td class="text-center"><span class="text-gray-dark">{{ $status->id }}</span></td>
                            <td>{{ $status->title }}</td>
                            <td class="text-center"><span class="badge badge-pill badge-{{ isset($status->color) && $status->color ? $status->color : 'light' }}">{{ $status->title }}</span></td>
                            <td class="text-center">{{ $status->sort_order }}</td>
                            <td class="text-right font-size-sm">
                                <button class="btn btn-sm btn-alt-secondary" onclick="event.preventDefault(); openModal({{ json_encode($status) }});">
                                    <i class="fa fa-fw fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-alt-danger" onclick="event.preventDefault(); deleteStatus({{ $status->id }});">
                                    <i class="fa fa-fw fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="4">{{ __('back/app.statuses.empty_list') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="status-modal" tabindex="-1" role="dialog" aria-labelledby="status--modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-popout" role="document">
            <div class="modal-content rounded">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary">
                        <h3 class="block-title">{{ __('back/app.statuses.main_title') }}</h3>
                        <div class="block-options">
                            <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row justify-content-center mb-3">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label for="status-title">{{ __('back/app.statuses.input_title') }}</label>
                                    <ul class="nav nav-pills float-right">
                                        @foreach(ag_lang() as $lang)
                                            <li @if (current_locale() == $lang->code) class="active" @endif>
                                                <a class="btn btn-sm btn-outline-secondary ml-2 @if (current_locale() == $lang->code) active @endif " data-toggle="pill" href="#{{ $lang->code }}">
                                                    <img src="{{ asset('media/flags/' . $lang->code . '.png') }}" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>


                                    <div class="tab-content">
                                        @foreach(ag_lang() as $lang)
                                            <div id="{{ $lang->code }}" class="tab-pane @if (current_locale() == $lang->code) active @endif">
                                                <input type="text" class="form-control" id="status-title-{{ $lang->code }}" name="title[{{ $lang->code }}]" placeholder="{{ $lang->code }}" value="">
                                                @error('title.*')
                                                <span class="text-danger font-italic">{{ __('back/app.geozone.enter_title') }}</span>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>



                                </div>

                                <div class="form-group">
                                    <label for="status-price">{{ __('back/app.statuses.sort_order') }}</label>
                                    <input type="text" class="form-control" id="status-sort-order" name="sort_order">
                                </div>

                                <div class="form-group">
                                    <label for="status-color-select">{{ __('back/app.statuses.color') }}</label>
                                    <select class="js-select2 form-control" id="status-color-select" name="status" style="width: 100%;" data-placeholder="{{ __('back/app.statuses.select_status') }}">
                                        <option value="primary">Primary</option>
                                        <option value="secondary">Secondary</option>
                                        <option value="success">Success</option>
                                        <option value="info">Info</option>
                                        <option value="light">Light</option>
                                        <option value="danger">Danger</option>
                                        <option value="warning">Warning</option>
                                        <option value="dark">Dark</option>
                                    </select>
                                </div>

                                <input type="hidden" id="status-id" name="id" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                            {{ __('back/app.statuses.cancel') }}<i class="fa fa-times ml-2"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-primary" onclick="event.preventDefault(); createStatus();">
                            {{ __('back/app.statuses.save') }} <i class="fa fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete-status-modal" tabindex="-1" role="dialog" aria-labelledby="status--modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-popout" role="document">
            <div class="modal-content rounded">
                <div class="block block-themed block-transparent mb-0">
                    <div class="block-header bg-primary">
                        <h3 class="block-title">{{ __('back/app.statuses.delete_status') }}</h3>
                        <div class="block-options">
                            <a class="text-muted font-size-h3" href="#" data-dismiss="modal" aria-label="Close">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="row justify-content-center mb-3">
                            <div class="col-md-10">
                                <h4>{{ __('back/app.statuses.delete_shure') }}</h4>
                                <input type="hidden" id="delete-status-id" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-right bg-light">
                        <a class="btn btn-sm btn-light" data-dismiss="modal" aria-label="Close">
                            {{ __('back/app.statuses.cancel') }} <i class="fa fa-times ml-2"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="event.preventDefault(); confirmDelete();">
                            {{ __('back/app.statuses.save') }} <i class="fa fa-trash-alt ml-2"></i>
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
            $('#status-color-select').select2({
                minimumResultsForSearch: Infinity,
                templateResult: formatColorOption,
                templateSelection: formatColorOption
            });
        });

        /**
         *
         * @param state
         * @return string
         */
        function formatColorOption(state) {
            if (!state.id) { return state.text; }

            let html = $(
                '<span class="badge badge-pill badge-' + state.element.value + '"> ' + state.text + ' </span>'
            );
            return html;
        }

        /**
         *
         * @param item
         * @param type
         */
        function openModal(item = {}) {
            //console.log(item);

            $('#status-modal').modal('show');
            editStatus(item);
        }

        /**
         *
         */
        function createStatus() {
            let item = {
                id: $('#status-id').val(),
                title: $('#status-title').val(),
                sort_order: $('#status-sort-order').val(),
                color: $('#status-color-select').val()
            };

            axios.post("{{ route('api.order.status.store') }}", {data: item})
            .then(response => {
                //console.log(response.data)
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
        function deleteStatus(id) {
            $('#delete-status-modal').modal('show');
            $('#delete-status-id').val(id);
        }

        /**
         *
         */
        function confirmDelete() {
            let item = {
                id: $('#delete-status-id').val()
            };

            axios.post("{{ route('api.order.status.destroy') }}", {data: item})
            .then(response => {
                //console.log(response.data)
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
        function editStatus(item) {
            $('#status-id').val(item.id);
            $('#status-title').val(item.title);
            $('#status-sort-order').val(item.sort_order);

            $('#status-color-select').val(item.color);
            $('#status-color-select').trigger('change');
        }
    </script>
@endpush
