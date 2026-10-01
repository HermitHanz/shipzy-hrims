<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Actions\Employees\ChangeEmployeeStatus;
use App\Http\Requests\Employees\ChangeStatusRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class EmployeeStatusController extends Controller
{
    public function update(ChangeStatusRequest $request, Employee $employee, ChangeEmployeeStatus $action): RedirectResponse
    {
        Gate::authorize('changeStatus', $employee);

        $data    = $request->validated();
        $details = $data['status'] === 'separated' ? Arr::only($data, ['separation_date', 'separation_reason']) : [];

        $action->handle($request->user(), $employee, $data['status'], $details);

        return to_route('employees.show', $employee)->withFragment('overview')->with('status', 'Employee status updated.');
    }
}