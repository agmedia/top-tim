@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
@endpush

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">API Postavke</h1>

                <a class="btn btn-hero-success my-2" href="{{ route('api.cron.reports') }}">
                    <i class="far fa-fw fa-plus-square"></i><span class="d-none d-sm-inline ml-1"> CRON Reports</span>
                </a>
            </div>
        </div>
    </div>

    <div class="content content-full">
        <div class="row">
            <div class="col-12">
                <div class="block block-rounded">
                    <ul class="nav nav-tabs nav-tabs-block" data-toggle="tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#import-tab">Import</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#export-tab">Export</a>
                        </li>
                        <li class="nav-item ml-auto">
                            <a class="nav-link" href="#settings-tab">
                                <i class="si si-settings"></i>
                            </a>
                        </li>
                    </ul>
                    <div class="block-content tab-content">
                        <div class="tab-pane active" id="import-tab" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8 mb-4">
                                    <div class="block block-rounded mb-1">
                                        <div class="block-content px-1">
                                            <div class="row items-push">
                                                <div class="col-md-12">
                                                    <table class="table table-responsive table-borderless table-vcenter">
                                                        <tbody>
                                                        <tr>
                                                            <td style="width: 20%;">
                                                                <input type="file" id="excel-file" name="file" accept=".xlsx,.xls" style="display: none;" onchange="uploadFile(event)">
                                                                <button class="btn btn-alt-info" onclick="document.getElementById('excel-file').click()">Upload Excel</button>
                                                            </td>
                                                            <td>
                                                                <code>Import proizvoda iz excel datoteke prema strogo definiranim poljima. Ako nazivi polja nisu zadovoljeni, import neće proći.</code>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="block block-rounded block-bordered" id="my-block-import">
                                        <div class="block-header block-header-default">
                                            <h3 class="block-title">Rezultat</h3>
                                        </div>
                                        <div class="block-content">
                                            <p class="font-w300 font-size-sm" id="api-result-import">Ovdje će se prikazati rezultati ili greške poziva...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="export-tab" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8 mb-4">
                                    <div class="block block-rounded mb-1">
                                        <div class="block-content px-1">
                                            <div class="row items-push">
                                                <div class="col-md-12">
                                                    <table class="table table-responsive table-borderless table-vcenter">
                                                        <tbody>
                                                        <tr class="mb-2">
                                                            <td style="width: 30%;">
                                                                <a href="{{ route('export.simple.excel') }}" class="btn btn-alt-info">Export Excel</a>
                                                            </td>
                                                            <td>
                                                                <code>Export proizvoda u excel datoteku i download iste. Samo osnovni podaci.</code>
                                                            </td>
                                                        </tr>
                                                        {{--<tr>
                                                            <td style="width: 30%;">
                                                                <a href="{{ route('export.excel') }}" class="btn btn-alt-info">Export Full Excel</a>
                                                            </td>
                                                            <td>
                                                                <code>Export proizvoda u excel datoteku i download iste. Full export sa opcijama, kategorijama, atributima, dodatnim fotografijama...</code>
                                                            </td>
                                                        </tr>--}}
                                                        <tr>
                                                            <td style="width: 30%; padding-top: 40px;">
                                                                <a href="{{ route('export.eracuni') }}" class="btn btn-alt-warning">Eračuni Export</a>
                                                            </td>
                                                            <td></td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="block block-rounded block-bordered" id="my-block-export">
                                        <div class="block-header block-header-default">
                                            <h3 class="block-title">Rezultat</h3>
                                        </div>
                                        <div class="block-content">
                                            @include('back.layouts.partials.session')
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="settings-tab" role="tabpanel">
                            <h4 class="font-w400">Settings Content</h4>
                            <p></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('modals')

@endpush

@push('js_after')
    <script>
        $(() => {

        });

        function importTarget(target, method) {
            let block = $('#my-block-import');
            let item  = {target: target, method: method};

            block.addClass('block-mode-loading');

            axios.post('{{ route('api.api.import') }}', {data: item})
            .then(response => {
                showToast(response.data);
                showResult(response.data, 'import');

                block.removeClass('block-mode-loading');
            });
        }


        function uploadFile(event) {
            let block = $('#my-block-import');
            let file = event.target.files[0];

            if (!file) {
                return errorToast.fire('Molimo učitajte Excel datoteku!');
            }

            let fd = new FormData();
            fd.append("file", file);
            fd.append("target", 'plava-krava');
            fd.append("method", 'upload-excel');

            block.addClass('block-mode-loading');

            axios.post('{{ route('api.api.upload') }}', fd)
            .then(response => {
                showToast(response.data);
                showResult(response.data, 'import');

                block.removeClass('block-mode-loading');
            });
        }


        function exportProducts() {
            axios.post('{{ route('api.api.export.products') }}', fd)
            .then(response => {
                showToast(response.data);
                showResult(response.data, 'import');

                block.removeClass('block-mode-loading');
            });
        }


        function showResult(result, type) {
            let text = result.success ? result.success : result.error;

            $('#api-result-' + type).html(text);
        }


        function showToast(result) {
            if (result.success) {
                successToast.fire();
            } else {
                errorToast.fire(result.message);
            }
        }

    </script>
@endpush
