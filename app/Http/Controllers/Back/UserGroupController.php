<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;

use App\Models\Back\UserGroup;
use App\Models\Back\UserGroupTranslation;
use App\Models\Front\Catalog\Brand;
use Illuminate\Http\Request;

class UserGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $query = (new UserGroup())->newQuery();
        if ($request->has('search') && ! empty($request->search)) {
            $value = $request->search;
            $user_groups = UserGroup::query()->whereHas('translation', function ($query) use ($value) {
                $query->where('title', 'like', '%' . $value . '%');
            })->paginate(30);

        } else {
            $user_groups = UserGroup::query()->paginate(30);
        }


        return view('back.user_group.index', compact('user_groups'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user_groups = new UserGroup();
        $groups = $user_groups->getList();


        return view('back.user_group.edit', compact('groups'));
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
        $user_groups = new UserGroup();

        $stored = $user_groups->validateRequest($request)->create();

        if ($stored) {


            return redirect()->route('user_groups.edit', ['user_groups' => $stored])->with(['success' => 'Brand je uspješno snimljen!']);
        }

        return redirect()->back()->with(['error' => 'Oops..! Greška prilikom snimanja.']);
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param Publisher $user_groups
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(UserGroup $user_groups)
    {

        $groups = $user_groups->getList();
        return view('back.user_group.edit', compact('user_groups', 'groups'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Publisher                $publisher
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserGroup $user_groups)
    {
        $updated = $user_groups->validateRequest($request)->edit();

        if ($updated) {

            return redirect()->route('user_groups.edit', ['user_groups' => $updated])->with(['success' => 'Grupa je uspješno snimljena!']);
        }

        return redirect()->back()->with(['error' => 'Oops..! Greška prilikom snimanja.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, UserGroup $user_groups)
    {
        $destroyed = UserGroup::destroy($user_groups->id);

        if ($destroyed) {
            UserGroupTranslation::query()->where('user_group_id', $user_groups->id)->delete();

            return redirect()->route('user_groups')->with(['success' => 'Grupa je uspješno izbrisana!']);
        }

        return redirect()->back()->with(['error' => 'Oops..! Greška prilikom brisanja.']);
    }






}
