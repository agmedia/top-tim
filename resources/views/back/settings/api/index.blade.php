@extends('back.layouts.backend')

@push('css_before')
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <style>
        /* manja slova za rezultat */
        #api-result-import { font-size: .85rem; line-height: 1.25; }
        /* progress bar nisko, tanko */
        .progress { height: 6px; }
        /* spinner u gumbu */
        .btn .spinner-border {
            width: 1rem; height: 1rem; border-width: .15em; display: none; margin-left: .5rem;
        }
        .btn.loading .spinner-border { display: inline-block; }
    </style>
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
                                Logs {{--<i class="si si-settings"></i>--}}
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
                                                                <input type="file" id="excel-file" name="file" accept=".xlsx,.xls" style="display: none;" onchange="uploadFile(event, 'simple-excel', 'upload-excel')">
                                                                <button class="btn btn-alt-info" onclick="document.getElementById('excel-file').click()">
                                                                    Upload Excel
                                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                                </button>
                                                            </td>
                                                            <td>
                                                                <code>Update proizvoda iz excel datoteke prema strogo definiranim poljima. Ako nazivi polja nisu zadovoljeni, import neće proći.</code>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width: 20%;">
                                                                <input type="file" id="excel-full-file" name="file" accept=".xlsx,.xls" style="display: none;" onchange="uploadFile(event, 'export-excel', 'import-from-excel')">
                                                                <button class="btn btn-alt-info" onclick="document.getElementById('excel-full-file').click()">
                                                                    Import Full Excel
                                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                                </button>
                                                            </td>
                                                            <td>
                                                                <code>Update proizvoda sa opcijama, atributima i kategorijama iz excel datoteke prema strogo definiranim poljima. Ako nazivi polja nisu zadovoljeni, import neće proći.</code>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="col-md-12 mt-4">
                                                    <form id="images-upload" action="{{ route('import.images.upload') }}" method="post" enctype="multipart/form-data" class="mb-4">
                                                        @csrf

                                                        <table class="table table-responsive table-borderless table-vcenter">
                                                            <tbody>
                                                            <tr>
                                                                <td style="width: 30%;">
                                                                    <button class="btn btn-primary" type="submit">Učitaj slike</button>
                                                                </td>
                                                                <td>
                                                                    <div class="mb-2">
                                                                        <code>Upload ZIP sa slikama:</code>
                                                                        <input type="file" name="zip" accept=".zip">
                                                                    </div>
                                                                    <div class="mb-2">
                                                                        <code>Ili odaberi više slika:</code>
                                                                        <input type="file" name="images[]" multiple accept="image/*">
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </form>

                                                    <div id="images-progress" class="d-none">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <small class="text-muted">Upload slika…</small>
                                                            <small id="images-progress-label" class="text-muted">0%</small>
                                                        </div>
                                                        <div class="progress mb-2">
                                                            <div id="images-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                                                 style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>

                                                    <script>
                                                        document.getElementById('images-upload').addEventListener('submit', async (e) => {
                                                            e.preventDefault();
                                                            const form = e.target;
                                                            const data = new FormData(form);
                                                            const res = await fetch(form.action, { method: 'POST', body: data });
                                                            const json = await res.json();
                                                            alert(json.msg || 'Gotovo');
                                                        });
                                                    </script>

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
                                            <p class="text-muted small mb-0" id="api-result-import">Ovdje će se prikazati rezultati ili greške poziva...</p>
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
                                                        <tr>
                                                            <td style="width: 30%;">
                                                                <a href="{{ route('export.excel') }}" class="btn btn-alt-info">Export Full Excel</a>
                                                            </td>
                                                            <td>
                                                                <code>Export proizvoda u excel datoteku i download iste. Full export sa opcijama, kategorijama, atributima, dodatnim fotografijama...</code>
                                                            </td>
                                                        </tr>
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
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="block block-rounded block-bordered mb-3">
                                        <div class="block-content block-content-full">
                                            <div class="font-size-sm text-muted">Queue čekanje</div>
                                            <div class="font-size-h3 mb-0">{{ (int)($queuePendingCount ?? 0) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="block block-rounded block-bordered mb-3">
                                        <div class="block-content block-content-full">
                                            <div class="font-size-sm text-muted">Queue obrada</div>
                                            <div class="font-size-h3 mb-0">{{ (int)($queueProcessingCount ?? 0) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="block block-rounded block-bordered mb-3">
                                        <div class="block-content block-content-full">
                                            <div class="font-size-sm text-muted">Zadnji završeni import</div>
                                            <div class="font-size-sm mb-0">
                                                @if(!empty($lastRun))
                                                    #{{ $lastRun->id }} / {{ \Illuminate\Support\Carbon::make($lastRun->created_at)->format('d.m.Y H:i:s') }}
                                                @else
                                                    Nema podataka
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="block block-rounded block-bordered">
                                <div class="block-header block-header-default">
                                    <h3 class="block-title">Import Full Excel log</h3>
                                </div>
                                <div class="block-content p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-vcenter mb-0">
                                            <thead>
                                            <tr>
                                                <th style="width:70px">#</th>
                                                <th style="width:120px">Tip</th>
                                                <th style="width:90px">Status</th>
                                                <th style="width:90px">Vrijeme</th>
                                                <th style="width:180px">Datum</th>
                                                <th>Poruka</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse(($importJobs ?? collect()) as $item)
                                                @php
                                                    $msg = trim(preg_replace('/\s+/', ' ', strip_tags((string)($item->response ?? ''))));
                                                @endphp
                                                <tr>
                                                    <td>{{ $item->id }}</td>
                                                    <td>{{ $item->type }}</td>
                                                    <td>@include('back.layouts.partials.status', ['simple' => true, 'status' => (int)$item->success])</td>
                                                    <td>{{ $item->time }}</td>
                                                    <td>{{ \Illuminate\Support\Carbon::make($item->created_at)->format('d.m.Y H:i:s') }}</td>
                                                    <td>{{ \Illuminate\Support\Str::limit($msg, 200) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Nema logova.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
        let importImagesDir = null;

        // helper: set loading state na block + gumb
        function setLoading($block, $btn, isLoading) {
            if (isLoading) {
                $block.addClass('block-mode-loading');
                if ($btn) { $btn.classList.add('loading'); $btn.disabled = true; }
            } else {
                $block.removeClass('block-mode-loading');
                if ($btn) { $btn.classList.remove('loading'); $btn.disabled = false; }
            }
        }

        function setProgress(wrapperId, barId, labelId, percent) {
            const wrap  = document.getElementById(wrapperId);
            const bar   = document.getElementById(barId);
            const label = document.getElementById(labelId);
            if (!wrap || !bar || !label) return;

            wrap.classList.remove('d-none');
            const p = Math.max(0, Math.min(100, Math.round(percent || 0)));
            bar.style.width = p + '%';
            bar.setAttribute('aria-valuenow', p);
            label.textContent = p + '%';

            if (p >= 100) {
                setTimeout(() => { wrap.classList.add('d-none'); }, 600);
            }
        }

        function showResult(result, type) {
            const text = result.success ? result.success : (result.error || result.message || 'Nepoznata greška.');
            document.getElementById('api-result-' + type).innerHTML = text;
        }

        function showToast(result) {
            if (result.success) {
                if (typeof successToast !== 'undefined') successToast.fire();
            } else {
                const msg = result.message || result.error || 'Greška.';
                if (typeof errorToast !== 'undefined') errorToast.fire(msg);
            }
        }

        // --- Upload Excel (s progress barom i spinnerom u gumbu) ---
        async function uploadFile(event, target, method) {
            const block = $('#my-block-import');
            const btn   = event.target.closest('td')?.querySelector('button.btn');
            const file  = event.target.files[0];

            if (!file) {
                if (typeof errorToast !== 'undefined') errorToast.fire('Molimo učitajte Excel datoteku!');
                return;
            }

            const fd = new FormData();
            fd.append("file", file);
            fd.append("target", target);
            fd.append("method", method);
            if (importImagesDir) {
                fd.append("images_dir", importImagesDir);
            }

            setLoading(block, btn, true);

            try {
                const res = await axios.post('{{ route('api.api.upload') }}', fd, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    onUploadProgress: (e) => {
                        if (e.total) {
                            const pct = (e.loaded / e.total) * 100;
                            setProgress('images-progress', 'images-progress-bar', 'images-progress-label', pct); // koristimo isti bar
                        }
                    }
                });

                showToast(res.data);
                showResult(res.data, 'import');
            } catch (err) {
                if (err && err.response && err.response.status === 413) {
                    showResult({ error: 'Datoteka je prevelika (HTTP 413). Povećaj limit ili podijeli na manje datoteke.' }, 'import');
                    if (typeof errorToast !== 'undefined') errorToast.fire('Datoteka je prevelika (413).');
                } else {
                    const msg = err?.response?.data?.error || err?.response?.data?.message || err?.message || 'Greška pri uploadu Excel-a.';
                    showResult({ error: msg }, 'import');
                    if (typeof errorToast !== 'undefined') errorToast.fire(msg);
                }
            } finally {
                setLoading(block, btn, false);
                // reset input da može ponovno birati istu datoteku
                event.target.value = '';
            }
        }

        // --- Upload slika (ZIP ili više slika) s progress barom ---
        document.getElementById('images-upload').addEventListener('submit', async (e) => {
            e.preventDefault();

            const form  = e.target;
            const block = $('#my-block-import');
            const btn   = form.querySelector('button[type="submit"]');

            const data = new FormData(form);

            setLoading(block, btn, true);
            setProgress('images-progress', 'images-progress-bar', 'images-progress-label', 0);

            try {
                const res = await axios.post(form.action, data, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    onUploadProgress: (e) => {
                        if (e.total) {
                            const pct = (e.loaded / e.total) * 100;
                            setProgress('images-progress', 'images-progress-bar', 'images-progress-label', pct);
                        }
                    }
                });

                importImagesDir = res.data?.images_dir || null;

                // API vraća {status, msg, images_dir}
                const payload = res.data?.status ? { success: res.data.msg } : { error: res.data.msg || 'Greška.' };
                showToast(payload);
                showResult(payload, 'import');
            } catch (err) {
                if (err && err.response && err.response.status === 413) {
                    showResult({ error: 'ZIP/slika prevelika (HTTP 413). Povećaj limit ili podijeli na manje.' }, 'import');
                    if (typeof errorToast !== 'undefined') errorToast.fire('Upload prevelik (413).');
                } else {
                    const msg = err?.response?.data?.error || err?.response?.data?.message || err?.message || 'Greška pri uploadu slika.';
                    showResult({ error: msg }, 'import');
                    if (typeof errorToast !== 'undefined') errorToast.fire(msg);
                }
            } finally {
                setLoading(block, btn, false);
                // ne resetiramo <input type="file"> jer user možda želi poslati isto opet
            }
        });

        // Ostalo (ako koristiš i ovo)
        async function importTarget(target, method) {
            const block = $('#my-block-import');
            setLoading(block, null, true);

            try {
                const { data } = await axios.post('{{ route('api.api.import') }}', { data: { target, method }});
                showToast(data);
                showResult(data, 'import');
            } catch (err) {
                const msg = err?.response?.data?.message || err?.message || 'Greška.';
                showResult({ error: msg }, 'import');
                if (typeof errorToast !== 'undefined') errorToast.fire(msg);
            } finally {
                setLoading(block, null, false);
            }
        }
    </script>
@endpush
