<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Access\AssignUserRoles;
use App\Actions\Access\CreateUserAccount;
use App\Actions\Access\ResetUserPassword;
use App\Actions\Access\SetUserStatus;
use App\Actions\Access\SyncUserPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPermissionsRequest;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\Employee;
use App\Models\User;
use App\Support\Access\AccessGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\Encryption\DecryptException;

class UserController extends Controller
{
    public function index(Request $request, AccessGuard $guard): View
    {
        Gate::authorize('viewAny', User::class);
        $actor = $request->user();

        $users = $guard->viewableUsers($actor)              // accounts at or below the actor's level
            ->with(['roles:id,name,label,level,is_system', 'employee'])
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
            ))
            ->when($request->input('role'), fn ($q, $role) => $q->role($role))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            // Filter dropdown only offers roles the actor could actually see users in
            'roles' => Role::where('level', '<=', $guard->levelOf($actor))->orderByDesc('level')->get(['id', 'name', 'label']),
        ]);
    }

    public function create(AccessGuard $guard): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', [
            'roles'     => $guard->assignableRoles(auth()->user()),
            'employees' => Employee::query()
                ->whereNotIn('id', User::whereNotNull('employee_id')->select('employee_id'))
                ->orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'employee_number', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAccount $action): RedirectResponse
    {
        [$user, $password] = $action->handle($request->user(), $request->validated());

        return to_route('admin.users.credentials')->with('credentials', [
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'email'     => $user->email,
            'password'  => Crypt::encryptString($password),   // encrypted while it sits in the session for one request
            'context'   => 'created',
        ]);
    }

    public function credentials(Request $request): Response|RedirectResponse
    {
        // pull() reads AND removes it, so a refresh or revisit finds nothing
        $credentials = $request->session()->pull('credentials');

        if (! $credentials) {
            return to_route('admin.users.index');
        }

        try {
            $credentials['password'] = Crypt::decryptString($credentials['password']);
        } catch (DecryptException) {
            return to_route('admin.users.index');
        }

        return response()
            ->view('admin.users.credentials', compact('credentials'))
            ->header('Cache-Control', 'no-store, private');
    }

    public function access(User $user, AccessGuard $guard): View
    {
        Gate::authorize('view', $user);                       // 403 if the actor may not even see this account
        $actor     = auth()->user();
        $canManage = Gate::allows('manageAccess', $user);     // false => page opens read-only

        $user->load(['roles.permissions', 'employee']);

        $inherited = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $inherited[$permission->name][] = $role->label ?: $role->name;
            }
        }

        $direct = $user->getDirectPermissions()->pluck('name')->all();

        $effective = [];
        foreach ($user->getAllPermissions() as $permission) {
            $effective[$permission->name] = [
                'roles'  => $inherited[$permission->name] ?? [],
                'direct' => in_array($permission->name, $direct, true),
            ];
        }
        ksort($effective);

        return view('admin.users.edit-access', [
            'user'              => $user,
            // Editable: only roles the actor can assign. Read-only: just show what the user holds.
            'roleOptions'       => $canManage ? $guard->assignableRoles($actor) : $user->roles->sortByDesc('level')->values(),
            'userRoleNames'     => $user->roles->pluck('name')->all(),
            'direct'            => $direct,
            'inherited'         => $inherited,
            'effectiveByModule' => collect($effective)->groupBy(fn ($v, $key) => explode('.', $key)[0], preserveKeys: true),
        ]);
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user, AssignUserRoles $action): RedirectResponse
    {
        $action->handle($request->user(), $user, $request->validated('roles', []));

        return to_route('admin.users.access', $user)->with('status', 'Roles updated.');
    }

    public function updatePermissions(UpdateUserPermissionsRequest $request, User $user, SyncUserPermissions $action): RedirectResponse
    {
        $action->handle($request->user(), $user, $request->validated('permissions', []));

        return to_route('admin.users.access', $user)->with('status', 'Direct permissions updated.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user, SetUserStatus $action): RedirectResponse
    {
        $action->handle($request->user(), $user, $request->validated('status'));

        return to_route('admin.users.access', $user)->with('status', 'Account status changed to ' . $request->validated('status') . '.');
    }

    public function resetPassword(Request $request, User $user, ResetUserPassword $action): RedirectResponse
    {
        $password = $action->handle($request->user(), $user);

        return to_route('admin.users.credentials')->with('credentials', [
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'email'     => $user->email,
            'password'  => Crypt::encryptString($password),
            'context'   => 'reset',
        ]);
    }

    private function assignableRoles(AccessGuard $guard)
    {
        $actor = auth()->user();

        return Role::orderByDesc('level')->get()
            ->filter(fn (Role $r) => $guard->canAssignRole($actor, $r))
            ->each(fn (Role $r) => $r->assignable = true)
            ->values();
    }
}