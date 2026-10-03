<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Actions\Employees\CreateEmployee;
use App\Actions\Employees\UpdateEmployee;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Support\Employees\EmployeeSections;
use App\Support\Employees\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::visibleTo($request->user(), 'employee.record.view')
            ->with(['department', 'branch', 'manager', 'user'])
            ->when(trim((string) $request->input('q')), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhereRaw("{$this->fullNameSql()} LIKE ?", ["%{$term}%"])
                    ->orWhere('employee_number', 'like', "%{$term}%")
                    ->orWhere('work_email', 'like', "%{$term}%")
            ))
            ->when($request->input('branch'), fn ($q, $v) => $q->where('branch_id', $v))
            ->when($request->input('department'), fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('employment_type'), fn ($q, $v) => $q->where('employment_type', $v))
            ->when($request->boolean('incomplete'), fn ($q) => $q->whereNull('onboarding_completed_at'))
            ->when($request->boolean('no_login'), fn ($q) => $q->whereDoesntHave('user'))
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(15)->withQueryString();

        // Filter options: only the units the viewer's visible employees belong to
        $visible = fn (string $column) => Employee::visibleTo($request->user(), 'employee.record.view')->select($column);

        return view('employees.index', [
            'employees'   => $employees,
            'branches'    => Branch::whereIn('id', $visible('branch_id'))->orderBy('name')->get(),
            'departments' => Department::whereIn('id', $visible('department_id'))->orderBy('name')->get(),
            'types'       => Options::types(),
            'statuses'    => Options::statuses(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Employee::class);

        return view('employees.create', $this->formData());
    }

    public function store(StoreEmployeeRequest $request, CreateEmployee $action): RedirectResponse
    {
        Gate::authorize('create', Employee::class);

        $employee = $action->handle($request->user(), $request->validated());

        return to_route('employees.show', $employee)
            ->with('status', 'Employee record created.')
            ->with('prompt_login', true);
    }

    public function show(Request $request, Employee $employee): View
    {
        Gate::authorize('view', $employee);

        return view('employees.show', [
            'employee' => $employee,
            'statuses' => Options::statuses(),
            ...EmployeeSections::data($employee, $request->user()),
        ]);
    }

    public function edit(Employee $employee): View
    {
        Gate::authorize('update', $employee);

        return view('employees.edit', ['employee' => $employee->load(['branch', 'department', 'manager'])] + $this->formData($employee));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, UpdateEmployee $action): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $action->handle($request->user(), $employee, $request->validated());

        return to_route('employees.show', $employee)->withFragment('overview')->with('status', 'Employee record updated.');
    }

    private function formData(?Employee $employee = null): array
    {
        return [
            // Active only, but keep the record's current value so the select can display it
            'branches'    => Branch::where('is_active', true)->when($employee?->branch_id, fn ($q, $id) => $q->orWhere('id', $id))->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->when($employee?->department_id, fn ($q, $id) => $q->orWhere('id', $id))->orderBy('name')->get(),
            'managers'    => Employee::where('status', '!=', 'separated')
                ->when($employee, fn ($q) => $q->where('id', '!=', $employee->id))
                ->orderBy('last_name')->orderBy('first_name')->get(),
            'types'       => Options::types(),
        ];
    }

    /** "First Last" as SQL. SQLite (the test database) has no CONCAT() before 3.44, so it uses ||. */
    private function fullNameSql(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "first_name || ' ' || last_name"
            : "CONCAT(first_name, ' ', last_name)";
    }
}