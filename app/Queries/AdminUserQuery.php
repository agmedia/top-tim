<?php

namespace App\Queries;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminUserQuery
{
    public static function filters(Request $request): array
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(['administrators', 'superadmin', 'admin', 'editor', 'customer', 'none'])],
            'sort' => ['nullable', Rule::in(['role', 'name', 'email', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        return [
            'search' => trim((string) ($data['search'] ?? '')),
            'role' => $data['role'] ?? '',
            'sort' => $data['sort'] ?? 'role',
            'direction' => $data['direction'] ?? 'asc',
        ];
    }

    public static function build(array $filters): Builder
    {
        $query = User::query()->select('users.*')->with(['details.group.translation', 'roles']);
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.email', 'like', '%' . $search . '%')
                    ->orWhereHas('details', function (Builder $details) use ($search) {
                        $details->where('fname', 'like', '%' . $search . '%')
                            ->orWhere('lname', 'like', '%' . $search . '%')
                            ->orWhere('company', 'like', '%' . $search . '%')
                            ->orWhere('oib', 'like', '%' . $search . '%')
                            ->orWhere('city', 'like', '%' . $search . '%');
                    });
            });
        }

        $role = $filters['role'] ?? '';
        if ($role === 'none') {
            $query->whereDoesntHave('roles');
        } elseif ($role !== '') {
            $names = $role === 'administrators' ? ['admin', 'superadmin'] : [$role];
            $query->whereHas('roles', function (Builder $roles) use ($names) {
                $roles->whereIn('name', $names);
            });
        }

        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $sort = $filters['sort'] ?? 'role';
        if ($sort === 'role') {
            // Use the actual authorization relation, including its morph/scope constraints.
            $roleColumn = DB::getQueryGrammar()->wrap((new User())->roles()->getRelated()->getTable() . '.name');
            $priority = "CASE {$roleColumn} WHEN 'superadmin' THEN 0 WHEN 'admin' THEN 1 WHEN 'editor' THEN 2 WHEN 'customer' THEN 3 ELSE 4 END";
            $query->withMin('roles as role_priority', DB::raw($priority))
                ->orderByRaw('role_priority IS NULL')
                ->orderBy('role_priority', $direction)
                ->orderBy('users.name');
        } else {
            $columns = ['name' => 'users.name', 'email' => 'users.email', 'created_at' => 'users.created_at'];
            $query->orderBy($columns[$sort] ?? 'users.name', $direction);
        }

        return $query->orderBy('users.id');
    }
}
