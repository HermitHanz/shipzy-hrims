<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Support\Employees\EmployeeSections;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return view('me.profile', ['employee' => null]);
        }

        Gate::authorize('view', $employee);

        return view('me.profile', ['employee' => $employee, ...EmployeeSections::data($employee, $request->user())]);
    }
}