<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\Recepti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ReceptiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('search') && ! empty($request->search)) {
            $receptis = Recepti::where('group', 'recepti')->where('title', 'like', '%' . $request->search . '%')->paginate(12);
        } else {
            $receptis = Recepti::where('group', 'recepti')->paginate(12);
        }

        return view('back.marketing.recepti.index', compact('receptis'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.marketing.recepti.edit');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $recepti = new Recepti();

        $stored = $recepti->validateRequest($request)->create();

        if ($stored) {
            $recepti->resolveImage($stored);

            return redirect()->route('receptis.edit', ['recepti' => $stored])->with(['success' => 'Recepti was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the recepti.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Recepti $recepti)
    {
        return view('back.marketing.recepti.edit', compact('recepti'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Author                   $author
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Recepti $recepti)
    {
        $updated = $recepti->validateRequest($request)->edit();

        if ($updated) {
            $recepti->resolveImage($updated);

            return redirect()->route('receptis.edit', ['recepti' => $updated])->with(['success' => 'Recepti was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the recepti.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Recepti $recepti)
    {
        $destroyed = Recepti::destroy($recepti->id);

        if ($destroyed) {
            return redirect()->route('receptis')->with(['success' => 'Recepti was succesfully deleted!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error deleting the recepti.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyApi(Request $request)
    {
        if ($request->has('id')) {
            $destroyed = Recepti::destroy($request->input('id'));

            if ($destroyed) {
                return response()->json(['success' => 200]);
            }
        }

        return response()->json(['error' => 300]);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadReceptiImage(Request $request)
    {
        if ( ! $request->hasFile('upload')) {
            return response()->json(['uploaded' => false]);
        }

        $recepti_id = $request->input('recepti_id');
        $img = $request->file('upload');
        $name = Str::random(9) . '_' . $img->getClientOriginalName();

        $path = '';

        if ($recepti_id) {
            $path = $recepti_id . '/';
        }

        Storage::disk('recepti')->putFileAs($path, $img, $name);

        return response()->json(['fileName' => $name, 'uploaded' => true, 'url' => url(config('filesystems.disks.recepti.url') . $path . $name)]);
    }
}
