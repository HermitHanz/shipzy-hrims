<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Access\CreateRole;
use App\Actions\Access\DeleteRole;
use App\Actions\Access\UpdateRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Support\Access\AccessGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => Role::query()->withCount(['users', 'permissions'])->orderByDesc('level')->orderBy('name')->get(),
        ]);
    }

    public function create(AccessGuard $guard): View
    {
        Gate::authorize('create', Role::class);

        return view('admin.roles.create', [
            'levels' => collect($guard->settableLevels(auth()->user()))->values(),
        ]);
    }


    public function store(StoreRoleRequest $request, CreateRole $action): RedirectResponse
    {
        $role = $action->handle($request->user(), $request->validated());

        return to_route('admin.roles.edit', $role)->with('status', "Role “{$role->label}” created.");
    }

    public function edit(Role $role, AccessGuard $guard): View
    {
        Gate::authorize('view', $role);
        $actor = auth()->user();

        $readOnly = $role->name === 'super-admin'
            || Gate::denies('update', $role)
            || ! $guard->canManageRole($actor, $role);

        return view('admin.roles.edit', [
            'role'       => $role,
            'readOnly'   => $readOnly,
            'levels'     => collect($guard->settableLevels($actor))->push((int) $role->level)->unique()->sort()->values(),
            'granted'    => $role->permissions()->pluck('name')->all(),
            'usersCount' => $role->users()->count(),
        ]);
}

    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $action): RedirectResponse
    {
        $action->handle($request->user(), $role, $request->validated());

        return to_route('admin.roles.edit', $role)->with('status', 'Role updated.');
    }

    public function destroy(Role $role, DeleteRole $action): RedirectResponse
    {
        $label = $role->label ?: $role->name;
        $action->handle(request()->user(), $role);

        return to_route('admin.roles.index')->with('status', "Role “{$label}” deleted.");
    }
}