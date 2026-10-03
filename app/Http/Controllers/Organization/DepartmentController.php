<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateDepartment;
use App\Actions\Organization\DeleteDepartment;
use App\Actions\Organization\SetDepartmentStatus;
use App\Actions\Organization\UpdateDepartment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreDepartmentRequest;
use App\Http\Requests\Organization\UpdateDepartmentRequest;
use App\Http\Requests\Organization\UpdateOrganizationStatusRequest;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Department::class);

        return view('organization.departments.index', [
            'departments' => Department::query()
                ->with(['parent', 'head'])
                ->withCount([
                    'employees as active_employees_count' => fn ($q) => $q->where('status', '!=', 'separated'),
                    // Any record at all (separated or soft-deleted too) means the department can't be deleted
                    'employees as employee_records_count' => fn ($q) => $q->withTrashed(),
                    'children',
                ])
                ->orderByDesc('is_active')->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Department::class);

        return view('organization.departments.create', $this->formData());
    }

    public function store(StoreDepartmentRequest $request, CreateDepartment $action): RedirectResponse
    {
        Gate::authorize('create', Department::class);

        $department = $action->handle($request->user(), $request->validated());

        return to_route('organization.departments.edit', $department)->with('status', "Department “{$department->name}” created.");
    }

    public function edit(Department $department): View
    {
        Gate::authorize('view', $department);

        return view('organization.departments.edit', [
            'department' => $department->loadMissing('parent'),
            'readOnly' => Gate::denies('update', $department),
            'activeEmployees' => $department->employees()->where('status', '!=', 'separated')->count(),
            'activeChildren' => $department->children()->where('is_active', true)->count(),
            ...$this->formData($department),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department, UpdateDepartment $action): RedirectResponse
    {
        Gate::authorize('update', $department);

        $action->handle($request->user(), $department, $request->validated());

        return to_route('organization.departments.edit', $department)->with('status', 'Department updated.');
    }

    public function updateStatus(UpdateOrganizationStatusRequest $request, Department $department, SetDepartmentStatus $action): RedirectResponse
    {
        Gate::authorize('deactivate', $department);

        $active = $request->boolean('is_active');
        $action->handle($request->user(), $department, $active);

        return to_route('organization.departments.edit', $department)->with('status', $active ? 'Department reactivated.' : 'Department deactivated.');
    }

    public function destroy(Request $request, Department $department, DeleteDepartment $action): RedirectResponse
    {
        Gate::authorize('delete', $department);

        $name = $department->name;
        $action->handle($request->user(), $department);

        return to_route('organization.departments.index')->with('status', "Department “{$name}” deleted.");
    }

    private function formData(?Department $department = null): array
    {
        return [
            // Active only, but keep the record's current value so the select can display it
            'parents' => Department::query()
                ->where(fn ($q) => $q->where('is_active', true)->when($department?->parent_id, fn ($q, $id) => $q->orWhere('id', $id)))
                ->when($department, fn ($q) => $q->where('id', '!=', $department->id))
                ->orderBy('name')->get(),
            'heads' => Employee::where('status', '!=', 'separated')
                ->when($department?->head_employee_id, fn ($q, $id) => $q->orWhere('id', $id))
                ->orderBy('last_name')->orderBy('first_name')->get(),
        ];
    }
}
