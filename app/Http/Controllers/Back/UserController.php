<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\UserGroup;
use App\Models\Front\Loyalty;
use App\Models\Roles\Role;
use App\Models\User;
use App\Queries\AdminUserQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;


use App\Exports\UsersExport;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless($request->user()
                && $request->user()->isActive()
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
        $filters = AdminUserQuery::filters($request);
        $users = AdminUserQuery::build($filters)->paginate(config('settings.pagination.back'))->appends($filters);
        $roleLabels = [
            'superadmin' => __('back/user.superadmin'),
            'admin' => __('back/user.admin'),
            'editor' => __('back/user.editor'),
            'customer' => __('back/user.customer'),
        ];

        return view('back.user.index', compact('users', 'filters', 'roleLabels'));
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
        $filename = 'users_' . now()->format('Y-m-d_H-i') . '.xlsx';

        return (new UsersExport(AdminUserQuery::filters($request)))->download($filename);
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
    public function destroy(Request $request, User $user)
    {
        $filters = AdminUserQuery::filters($request);
        abort_if((int) $request->user()->id === (int) $user->id, 403);
        $this->authorizeManagedUser($user);

        DB::transaction(function () use ($request, $user) {
            // Consistent lock order also protects against two staff deleting each other.
            $locked = User::whereIn('id', [$request->user()->id, $user->id])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $actor = $locked->get($request->user()->id);
            $target = $locked->get($user->id);

            abort_unless($actor && $actor->isActive()
                && $actor->roles()->whereIn('name', ['admin', 'superadmin'])->exists(), 403);
            abort_unless($target, 404);
            $this->authorizeManagedUser($target);

            $target->details()->update(['status' => 0]);
            $target->tokens()->delete();
            if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $target->id)->delete();
            }
            $target->delete();

            Log::notice('User deleted by administrator', ['actor_id' => $actor->id, 'user_id' => $target->id]);
        });

        return redirect()->route('users', $filters)
            ->with('success', __('back/user.deleted'));
    }

    private function authorizeManagedUser(User $user): void
    {
        if ($user->roles()->where('name', 'superadmin')->exists()) {
            abort_unless(auth()->user()->roles()->where('name', 'superadmin')->exists(), 403);
        }
    }
}
