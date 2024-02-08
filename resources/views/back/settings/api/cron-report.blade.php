@extends('back.layouts.backend')

@section('content')

    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-sm-fill font-size-h2 font-w400 mt-2 mb-0 mb-sm-2">CRON Report</h1>
            </div>
        </div>
    </div>

    <div class="content">
    @include('back.layouts.partials.session')
    <!-- All Products -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Svi izvještaji <small class="font-weight-light">{{ $crons->total() }}</small></h3>
                <div class="block-options">
                    <!-- Search Form -->
                    <form action="{{ route('api.cron.reports') }}" method="GET">
                        <div class="block-options-item">
                            <input type="text" class="form-control" id="search-input" name="search" placeholder="Pretraži izvještaje" value="{{ request()->query('search') }}">
                        </div>
                        <div class="block-options-item">
                            <a href="{{ route('api.cron.reports') }}" class="btn btn-hero-sm btn-secondary"><i class="fa fa-search-minus"></i> Očisti</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <div class="table-responsive">
                                <table class="table table-borderless table-striped table-vcenter">
                                    <thead>
                                    <tr>
                                        <th style="width: 30px;">#</th>
                                        <th>Vrijeme</th>
                                        <th>Tip</th>
                                        <th style="width: 70px;" class="text-center">Uspjeh</th>
                                        <th style="width: 100px;" class="text-right">Akcija</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($crons as $cron)
                                        <tr>
                                            <td>{{ $cron->id }}</td>
                                            <td>{{ \Illuminate\Support\Carbon::make($cron->created_at)->format('d.m.Y - H:i') }}</td>
                                            <td>{{ $cron->target }}</td>
                                            <td class="text-center">@include('back.layouts.partials.status', ['simple' => true, 'status' => $cron->success])</td>
                                            <td class="text-right">
                                                <a href="javascript:void(0);" onclick="viewCron({{ $cron }})">
                                                    <i class="fa fa-fw fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Pagination -->
                        {{ $crons->links() }}
                    </div>
                    <div class="col-md-4">
                        <div class="block block-rounded block-fx-pop">
                            <div class="block-header">
                                <div class="flex-fill text-muted font-size-sm font-w600">
                                    <i class="fa fa-clock mr-1"></i> <span id="cron-title"></span>
                                </div>
                            </div>
                            <div class="block-content bg-body text-center">
                                <h1 id="cron-status" class="font-size-h1 font-w700 mb-3"></h1>
                            </div>
                            <div class="block-content text-center">
                                <h5 id="cron-report"></h5>
                            </div>
                            <div class="block-content block-content-full">
                                <div class="row gutters-tiny">
                                    <p id="cron-data" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END All Products -->
    </div>
@endsection

@push('js_after')
    <script>
        function viewCron(cron) {
            console.log(cron)

            let title = document.getElementById('cron-title');
            title.textContent = cron.target;

            let status = document.getElementById('cron-status');
            status.innerHTML = cron.success ? '<i class="fa fa-fw fa-check text-success"></i>' : '<i class="fa fa-fw fa-times text-danger"></i>';

            let report = document.getElementById('cron-report');
            report.innerHTML = cron.response;

            let data = document.getElementById('cron-data');
            let datum = new Date(cron.created_at);
            data.innerHTML = 'Datum: <b>' + datum.toLocaleDateString('hr') + ' u ' + datum.toLocaleTimeString() + '</b><br>Trajanje: <b>' + cron.time + '</b> sec. <br>Količina: <b>' + Number(cron.payload).toLocaleString('hr') + '</b>';

        }
    </script>
@endpush
