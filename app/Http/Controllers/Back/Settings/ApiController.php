<?php

namespace App\Http\Controllers\Back\Settings;

use App\Helpers\ApiHelper;
use App\Helpers\Csv;
use App\Http\Controllers\Controller;
use App\Models\Back\Jobs;
use App\Models\Back\Settings\Api\AkademskaKnjigaMk;
use App\Models\Back\Settings\Api\Export;
use App\Models\Back\Settings\Api\PlavaKrava;
use App\Models\Back\Settings\Api\SimpleExcel;
use App\Models\Back\Settings\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $importJobs = Jobs::query()
            ->where('target', 'Import Full Excel')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $queuePendingCount = Jobs::query()
            ->where('type', 'cron_queue')
            ->where('target', 'Import Full Excel')
            ->where('send_report', 1)
            ->count();

        $queueProcessingCount = Jobs::query()
            ->where('type', 'cron_queue')
            ->where('target', 'Import Full Excel')
            ->where('send_report', 2)
            ->count();

        $lastRun = Jobs::query()
            ->where('type', 'cron')
            ->where('target', 'Import Full Excel')
            ->orderByDesc('id')
            ->first();

        return view('back.settings.api.index', compact(
            'importJobs',
            'queuePendingCount',
            'queueProcessingCount',
            'lastRun'
        ));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function cronReports(Request $request)
    {
        $crons = Jobs::filter($request, 'cron')->paginate(20);

        return view('back.settings.api.cron-report', compact('crons'));
    }


    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $data = $this->validateTarget($request);

        $targetClass = $this->resolveTargetClass($data);

        if ($targetClass) {
            $ok = $targetClass->process($data);

            if ($ok) {
                return response()->json(['success' => $ok]);
            }
        }

        return response()->json(['error' => 'Whoops.!! Pokušajte ponovo ili kontaktirajte administratora!']);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function upload(Request $request)
    {
        $request->validate([
            'target' => 'required',
            'method' => 'required',
            'file' => 'file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel'
        ]);

        Log::info($request->all());

        $targetClass = $this->resolveTargetClass($request->toArray());

        if ($targetClass) {
            $path = $targetClass->upload($request);

            if ($path) {
                if ($this->shouldQueueFullExcelImport($request)) {
                    $imagesDir = $this->resolveImportImagesDir($request);

                    if ($imagesDir === '') {
                        Log::warning('Full excel import queue rejected - images dir missing', [
                            'session_images_dir' => session('import_images_dir'),
                            'request_images_dir' => $request->input('images_dir'),
                        ]);

                        return response()->json([
                            'error' => "Import slika: direktorij nije postavljen ili ne postoji. Prvo učitaj slike pa pokreni import.",
                            'message' => "Import slika: direktorij nije postavljen ili ne postoji. Prvo učitaj slike pa pokreni import."
                        ], 422);
                    }

                    $jobId = $this->queueFullExcelImport($path, $imagesDir);

                    if (!$jobId) {
                        $this->deleteTempExcel($path);

                        return response()->json([
                            'error' => 'Import job nije upisan u red za obradu.',
                            'message' => 'Import job nije upisan u red za obradu.'
                        ], 500);
                    }

                    return response()->json([
                        'success' => ApiHelper::response(1, "Import je stavljen u red (#{$jobId}). Cron ga obrađuje svake minute.")
                    ]);
                }

                $excel = new Csv($path, 'Xlsx');

                $ok = $targetClass->process($request->toArray(), $excel->csv->toArray());

                if ($ok) {
                    return response()->json(['success' => $ok]);
                }
            }
        }


        return response()->json(['success' => $request->toArray()]);
    }


    /**
     * @param Request $request
     * @param string  $type
     *
     * @return mixed
     */
    public function validateTarget(Request $request, string $type = 'import')
    {
        $request->validate([
            'data.target' => 'required',
            'data.method' => 'required'
        ]);

        $data = $request->input('data');

        $request->merge([
            'data' => [
                'target' => $data['target'],
                'method' => $data['method'],
                'type' => $type
            ]
        ]);

        return $request->input('data');
    }


    public function exportProducts(Request $request)
    {
        $export = new Export();

        $export->toExcel();

        return response()->json(['success' => $request->toArray()]);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
  /*  public function exportToEracuni(Request $request)
    {
        $export = new Export();

        $exported = $export->toExcel();

        if ($exported) {
            return redirect()->route('dashboard')->with(['success' => 'Proizvodi su exportani!']);
        }

        return redirect()->route('dashboard')->with(['error' => 'Greška prilikom Exporta, molimo pokušajte ponovo ili kontaktirajte administratora!']);
    }*/
    public function exportToEracuni(Request $request)
    {
        $export = new \App\Models\Back\Settings\Api\Export();
        return $export->toCsvResponse();
    }




    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportToSimpleExcel(Request $request)
    {
        $export = new Export();

        $exported = $export->toSimpleExcel();

        if ($exported) {
            return back()->with(['success' => 'Proizvodi su exportani!']);
        }

        return back()->with(['error' => 'Greška prilikom Exporta, molimo pokušajte ponovo ili kontaktirajte administratora!']);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportToExcel(Request $request)
    {
        $export = new Export();

        $exported = $export->toExcel();

        if ($exported) {
            return back()->with(['success' => 'Proizvodi su exportani!']);
        }

        return back()->with(['error' => 'Greška prilikom Exporta, molimo pokušajte ponovo ili kontaktirajte administratora!']);
    }


    /**
     * Prima ili ZIP s fotkama ili više pojedinačnih slika.
     * Sve raspakira / spremi u storage/app/imports/images/{token}/
     * i token (apsolutnu baznu putanju) spremi u session 'import_images_dir'.
     */
    public function uploadImages(Request $request)
    {
        Log::info('uploadImages(Request $request):::');

        if (!$request->hasFile('images') && !$request->hasFile('zip')) {
            return response()->json(['status' => 0, 'msg' => 'Nije poslana datoteka (images[] ili zip).'], 422);
        }

        $token = 'img_' . now()->format('Ymd_His') . '_' . Str::random(6);
        $base  = "imports/images/{$token}";
        Storage::makeDirectory($base);

        // Varijanta A: ZIP (jedan fajl)
        if ($request->hasFile('zip')) {
            $zipFile = $request->file('zip');
            $zipPath = $zipFile->storeAs($base, 'upload.zip');

            $zip = new \ZipArchive();
            $abs = storage_path('app/' . $zipPath);
            if ($zip->open($abs) === true) {
                $zip->extractTo(storage_path('app/' . $base));
                $zip->close();
                Storage::delete($zipPath);
            }
        }

        // Varijanta B: više pojedinačnih slika
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $img->storeAs($base, $img->getClientOriginalName());
            }
        }

        $maxMb = 200;
        $maxKb = $maxMb * 1024;

        $request->validate([
            'zip'        => "nullable|file|mimes:zip|max:{$maxKb}",
            'images'     => 'nullable|array',
            'images.*'   => "file|mimes:jpg,jpeg,png,webp|max:{$maxKb}",
        ], [
            'zip.max'       => "ZIP je veći od {$maxMb}MB. Podijeli na više manjih ili kontaktiraj podršku.",
            'images.*.max'  => "Pojedinačna slika je veća od {$maxMb}MB.",
            'zip.mimes'     => 'Dozvoljen je samo ZIP.',
            'images.*.mimes'=> 'Dozvoljeni formati: jpg, jpeg, png, webp.',
        ]);

        // Spremi apsolutnu putanju u session da ju import/validacija koristi
        $absBase = storage_path('app/' . $base);
        session(['import_images_dir' => $absBase]);

        Log::info('Upload images: ' . $absBase);

        return response()->json([
            'status' => 1,
            'msg'    => 'Slike učitane.',
            'images_dir' => $absBase
        ]);
    }


    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param array $data
     *
     * @return mixed
     */
    private function resolveTargetClass(array $data)
    {
        $class = null;

        if (isset($data['target'])) {
            if ($data['target'] == 'simple-excel') {
                $class = new SimpleExcel();
            }
            if ($data['target'] == 'plava-krava') {
                $class = new PlavaKrava();
            }
            if ($data['target'] == 'export-excel') {
                $class = new Export();
            }
        }

        return $class;
    }


    /**
     * Full excel import (export-excel/import-from-excel) treba ići u pozadinski job.
     */
    private function shouldQueueFullExcelImport(Request $request): bool
    {
        return $request->input('target') === 'export-excel'
            && $request->input('method') === 'import-from-excel';
    }


    /**
     * Upisuje full-excel import u internu cron queue tablicu (jobs).
     */
    private function queueFullExcelImport(string $filePath, string $imagesDir): int
    {
        $payload = json_encode([
            'file' => $filePath,
            'images_dir' => $imagesDir,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($payload) || $payload === '') {
            return 0;
        }

        return Jobs::query()->insertGetId([
            'type' => 'cron_queue',
            'target' => 'Import Full Excel',
            'time' => '00:00',
            'success' => 0,
            'payload' => $payload,
            'response' => 'Na čekanju cron obrade.',
            'send_report' => 1,
            'created_at' => now(),
        ]);
    }


    private function deleteTempExcel(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (is_file($path)) {
            @unlink($path);
        }
    }


    /**
     * Prefer request payload (for routes without shared session context),
     * fallback to session value set by uploadImages().
     */
    private function resolveImportImagesDir(Request $request): string
    {
        $fromRequest = trim((string) $request->input('images_dir'));
        $fromSession = trim((string) session('import_images_dir'));
        $imagesDir   = $fromRequest !== '' ? $fromRequest : $fromSession;

        if ($imagesDir === '' || !is_dir($imagesDir)) {
            return '';
        }

        $allowedRoot = realpath(storage_path('app/imports/images'));
        $realDir     = realpath($imagesDir);

        if ($allowedRoot && $realDir && strpos($realDir, $allowedRoot) === 0) {
            return $realDir;
        }

        return '';
    }

}
