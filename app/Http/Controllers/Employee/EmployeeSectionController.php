<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Actions\Employees\SaveEmergencyContacts;
use App\Actions\Employees\SaveGovernmentIds;
use App\Actions\Employees\SubmitBankAccount;
use App\Actions\Employees\UpdatePersonalDetails;
use App\Http\Requests\Employees\BankAccountRequest;
use App\Http\Requests\Employees\EmergencyContactsRequest;
use App\Http\Requests\Employees\GovernmentIdsRequest;
use App\Http\Requests\Employees\PersonalDetailsRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeSectionController extends Controller
{
    public function personal(PersonalDetailsRequest $request, UpdatePersonalDetails $action): RedirectResponse
    {
        $employee = $this->target($request);
        Gate::authorize('updatePersonal', $employee);

        $action->handle($request->user(), $employee, $request->validated());

        return $this->done($request, $employee, 'personal', 'Personal details saved.');
    }

    public function emergency(EmergencyContactsRequest $request, SaveEmergencyContacts $action): RedirectResponse
    {
        $employee = $this->target($request);
        Gate::authorize('updateEmergency', $employee);

        $action->handle($request->user(), $employee, array_values($request->validated('contacts') ?? []));

        return $this->done($request, $employee, 'emergency', 'Emergency contacts saved.');
    }

    public function governmentIds(GovernmentIdsRequest $request, SaveGovernmentIds $action): RedirectResponse
    {
        $employee = $this->target($request);
        Gate::authorize('updateGovernmentIds', $employee);

        // Only the keys the form sent (changed or removed) are present
        $action->handle($request->user(), $employee, $request->validated());

        return $this->done($request, $employee, 'ids', 'Government IDs updated.');
    }

    public function storeBankAccount(BankAccountRequest $request, SubmitBankAccount $action): RedirectResponse
    {
        $employee = $this->target($request);
        Gate::authorize('updateBank', $employee);

        $action->handle($request->user(), $employee, $request->validated());

        return $this->done($request, $employee, 'bank', 'Bank account submitted. It stays pending until it is verified.');
    }

    private function target(Request $request): Employee
    {
        $employee = $request->route('employee');

        // These methods don't type-hint Employee, so {employee} on the HR routes isn't bound implicitly
        if ($employee !== null && ! $employee instanceof Employee) {
            $employee = Employee::findOrFail($employee);
        }

        $employee ??= $request->user()->employee;
        abort_unless($employee instanceof Employee, 404);

        return $employee;
    }

    private function done(Request $request, Employee $employee, string $section, string $message): RedirectResponse
    {
        $to = $request->routeIs('profile.*') ? route('profile.show') : route('employees.show', $employee);

        return redirect()->to($to)->withFragment($section)->with('status', $message);
    }
}