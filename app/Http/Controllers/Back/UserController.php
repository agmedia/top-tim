<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\UserGroup;
use App\Models\Front\Loyalty;
use App\Models\Roles\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;


use App\Exports\UsersExport;

use Maatwebsite\Excel\Excel as ExcelWriter;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless($request->user()
                && $request->user()->roles()->whereIn('name', ['admin', 'superadmin'])->exists(), 403);

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = (new User())->newQuery();

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $users = $query->with('details')->paginate(config('settings.pagination.back'));

        return view('back.user.index', compact('users'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.user.edit');
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
        $user = new User();


    }

    public function export(Request $request)
    {
        $search = $request->query('search');
        $filename = 'users_' . now()->format('Y-m-d_H-i') . '.xlsx';

        return app(ExcelWriter::class)->download(new UsersExport($search), $filename, ExcelWriter::XLSX);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param User $user
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $this->authorizeManagedUser($user);

        $roles = Role::selectList();
        $points = Loyalty::hasLoyaltyTotal($user->id);
        $user_groups = (new UserGroup())->getList();

        return view('back.user.edit', compact('user', 'roles', 'points', 'user_groups'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param User                     $user
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeManagedUser($user);

        $roles = ['customer', 'editor', 'admin'];
        if ($request->user()->roles()->where('name', 'superadmin')->exists()) {
            $roles[] = 'superadmin';
        }
        $request->validate(['role' => ['required', 'string', Rule::in($roles)]]);

        $updated = $user->validateRequest($request)->edit();

        if ($updated) {
            Cache::flush();

            return redirect()->route('users.edit', ['user' => $updated])->with(['success' => 'Korisnik je uspješno snimljen!']);
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
    public function destroy(Request $request) {}

    private function authorizeManagedUser(User $user): void
    {
        if ($user->roles()->where('name', 'superadmin')->exists()) {
            abort_unless(auth()->user()->roles()->where('name', 'superadmin')->exists(), 403);
        }
    }
}
