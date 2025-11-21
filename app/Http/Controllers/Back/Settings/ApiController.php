<?php

namespace App\Http\Controllers\Back\Settings;

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
        return view('back.settings.api.index');
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
    public function exportToEracuni(Request $request)
    {
        $export = new Export();

        $exported = $export->toExcel();

        if ($exported) {
            return redirect()->route('dashboard')->with(['success' => 'Proizvodi su exportani!']);
        }

        return redirect()->route('dashboard')->with(['error' => 'Greška prilikom Exporta, molimo pokušajte ponovo ili kontaktirajte administratora!']);
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

}
