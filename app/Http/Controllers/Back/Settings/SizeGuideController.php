<?php

namespace App\Http\Controllers\Back\Settings;

use App\Http\Controllers\Controller;
use App\Models\Back\Settings\SizeGuide;
use Illuminate\Http\Request;

class SizeGuideController extends Controller
{
    /**
     * Display a listing of the resource.
     *x
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('search') && ! empty($request->search)) {
            $sizeguides = SizeGuide::where('title', 'like', '%' . $request->search . '%')->paginate(12);
        } else {
            $sizeguides = SizeGuide::paginate(12);
        }

        return view('back.settings.sizeguide.index', compact('sizeguides'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.settings.sizeguide.edit');
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
        $sizeguide = new SizeGuide();

        $stored = $sizeguide->validateRequest($request)->create();

        if ($stored) {

            $sizeguide->resolveImage($stored);

            return redirect()->route('sizeguides.edit', ['sizeguide' => $stored])->with(['success' => 'SizeGuide was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the sizeguide.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(SizeGuide $sizeguide)
    {
        return view('back.settings.sizeguide.edit', compact('sizeguide'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Author                   $author
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SizeGuide $sizeguide)
    {
        $updated = $sizeguide->validateRequest($request)->edit();

        if ($updated) {

            $sizeguide->resolveImage($updated);
            return redirect()->route('sizeguides.edit', ['sizeguide' => $updated])->with(['success' => ' SizeGuide was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the  SizeGuide.']);
    }




    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, SizeGuide $sizeguide)
    {
        $destroyed = SizeGuide::destroy($sizeguide->id);

        if ($destroyed) {
            return redirect()->route('sizeguides')->with(['success' => 'sizeguide was succesfully deleted!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error deleting the sizeguide.']);
    }
}
