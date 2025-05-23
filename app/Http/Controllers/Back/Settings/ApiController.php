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
        }

        return $class;
    }

}
