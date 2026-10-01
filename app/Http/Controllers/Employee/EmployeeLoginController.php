<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Actions\Employees\CreateEmployeeLogin;
use App\Http\Requests\Employees\CreateLoginRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;

class EmployeeLoginController extends Controller
{
    public function store(CreateLoginRequest $request, Employee $employee, CreateEmployeeLogin $action): RedirectResponse
    {
        Gate::authorize('create', User::class);

        [$user, $password] = $action->handle($request->user(), $employee, $request->validated('email'));

        return to_route('admin.users.credentials')->with('credentials', [
            'user_id'    => $user->id,
            'user_name'  => $user->name,
            'email'      => $user->email,
            'password'   => Crypt::encryptString($password),
            'context'    => 'created',
            'back_url'   => route('employees.show', $employee) . '#login',
            'back_label' => 'Back to employee',
        ]);
    }
}