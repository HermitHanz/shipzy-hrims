<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateBranch;
use App\Actions\Organization\DeleteBranch;
use App\Actions\Organization\SetBranchStatus;
use App\Actions\Organization\UpdateBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreBranchRequest;
use App\Http\Requests\Organization\UpdateBranchRequest;
use App\Http\Requests\Organization\UpdateOrganizationStatusRequest;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Branch::class);

        return view('organization.branches.index', [
            'branches' => Branch::query()
                ->withCount([
                    'employees as active_employees_count' => fn ($q) => $q->where('status', '!=', 'separated'),
                    // Any record at all (separated or soft-deleted too) means the branch can't be deleted
                    'employees as employee_records_count' => fn ($q) => $q->withTrashed(),
                ])
                ->orderByDesc('is_active')->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Branch::class);

        return view('organization.branches.create');
    }

    public function store(StoreBranchRequest $request, CreateBranch $action): RedirectResponse
    {
        Gate::authorize('create', Branch::class);

        $branch = $action->handle($request->user(), $request->validated());

        return to_route('organization.branches.edit', $branch)->with('status', "Branch “{$branch->name}” created.");
    }

    public function edit(Branch $branch): View
    {
        Gate::authorize('view', $branch);

        return view('organization.branches.edit', [
            'branch' => $branch,
            'readOnly' => Gate::denies('update', $branch),
            'activeEmployees' => $branch->employees()->where('status', '!=', 'separated')->count(),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch, UpdateBranch $action): RedirectResponse
    {
        Gate::authorize('update', $branch);

        $action->handle($request->user(), $branch, $request->validated());

        return to_route('organization.branches.edit', $branch)->with('status', 'Branch updated.');
    }

    public function updateStatus(UpdateOrganizationStatusRequest $request, Branch $branch, SetBranchStatus $action): RedirectResponse
    {
        Gate::authorize('deactivate', $branch);

        $active = $request->boolean('is_active');
        $action->handle($request->user(), $branch, $active);

        return to_route('organization.branches.edit', $branch)->with('status', $active ? 'Branch reactivated.' : 'Branch deactivated.');
    }

    public function destroy(Request $request, Branch $branch, DeleteBranch $action): RedirectResponse
    {
        Gate::authorize('delete', $branch);

        $name = $branch->name;
        $action->handle($request->user(), $branch);

        return to_route('organization.branches.index')->with('status', "Branch “{$name}” deleted.");
    }
}
