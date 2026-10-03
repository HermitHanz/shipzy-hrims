@php
    $plural = fn (string $word, int $n) => $n . ' active ' . \Illuminate\Support\Str::plural($word, $n);

    if ($department->is_active) {
        $blockReason = match (true) {
            $activeEmployees > 0 => 'Reassign its ' . $plural('employee', $activeEmployees) . ' before deactivating it.',
            $activeChildren > 0 => 'Deactivate its ' . $plural('sub-department', $activeChildren) . ' first.',
            default => null,
        };
    } else {
        $blockReason = $department->parent && ! $department->parent->is_active
            ? "Reactivate the parent department “{$department->parent->name}” first."
            : null;
    }
@endphp
<x-layouts.app :title="'Department · ' . $department->name">
    <x-slot:header>
        <x-ui.page-header :title="$department->name" :subtitle="$department->code . ' · ' . $plural('employee', $activeEmployees)">
            <x-ui.badge :variant="$department->is_active ? 'green' : 'gray'">{{ $department->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
        </x-ui.page-header>
    </x-slot:header>

    @if ($readOnly)
        <div role="status" class="mb-6 rounded-xl bg-slate-100 p-4 text-sm text-slate-700 ring-1 ring-slate-200">You can view this department but not change it.</div>
    @endif

    <div class="max-w-3xl space-y-6">
        @include('organization.departments._form', ['department' => $department, 'readOnly' => $readOnly, 'parents' => $parents, 'heads' => $heads])

        @can('deactivate', $department)
            @include('organization.partials.status', [
                'active' => $department->is_active,
                'action' => route('organization.departments.status.update', $department),
                'noun' => 'department',
                'blockReason' => $blockReason,
                'deactivateNote' => "New employees can't be assigned to it and it can't be picked as a parent. Its history is kept.",
            ])
        @endcan
    </div>
</x-layouts.app>
