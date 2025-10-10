<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Marketing\Action;
use App\Models\Back\Settings\Settings;
use App\Models\Back\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ActionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $actions = Action::query();

        if ($request->has('search')) {
            $actions->whereHas('userGroup', function ($query) use ($request) {
                $query->whereHas('translation', function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->input('search') . '%');
                });
            });
        }

        $actions = $actions->paginate(12);

        $user_groups = (new UserGroup())->getList();

        return view('back.marketing.action.index', compact('actions', 'user_groups'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $groups = Settings::get('action', 'group_list');
        $types = Settings::get('action', 'type_list');
        $user_groups = (new UserGroup())->getList();

        // Capture pre-fill query params (if any)
        $prefillGroup = $request->query('group');              // e.g. "product"
        $prefillList  = $request->query('action_list', []);    // e.g. [123, ...], default empty array
        $prefillTitle = $request->query('title', ''); // nova linija

        return view('back.marketing.action.edit', compact('groups', 'types', 'user_groups', 'prefillGroup', 'prefillList', 'prefillTitle'));
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
        //dd($request->toArray());
        $action = new Action();

        $stored = $action->validateRequest($request)->create();

        if ($stored) {
            Cache::flush();

            return redirect()->route('actions.edit', ['action' => $stored])->with(['success' => 'Action was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the action.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Action $action)
    {
        $groups = Settings::get('action', 'group_list');
        $types = Settings::get('action', 'type_list');
        $user_groups = (new UserGroup())->getList();

        return view('back.marketing.action.edit', compact('action', 'groups', 'types', 'user_groups'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Author                   $author
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Action $action)
    {
        $updated = $action->validateRequest($request)->edit();

        if ($updated) {
            Cache::flush();

            return redirect()->route('actions.edit', ['action' => $updated])->with(['success' => 'Action was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the action.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Action $action)
    {
        $destroyed = $action->resolveDestruction($action->id);

        if ($destroyed) {
            Cache::flush();

            return redirect()->route('actions')->with(['success' => 'Akcija je uspjšeno izbrisana!']);
        }

        return redirect()->back()->with(['error' => 'Oops..! Greška prilikom brisanja.']);
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
            $action = new Action();
            $destroyed = $action->resolveDestruction($request->input('id'));

            if ($destroyed) {
                Cache::flush();

                return response()->json(['success' => 200]);
            }
        }

        return response()->json(['error' => 300]);
    }
}
