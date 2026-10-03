@php
    $blockReason = $branch->is_active && $activeEmployees > 0
        ? "Reassign its {$activeEmployees} active " . \Illuminate\Support\Str::plural('employee', $activeEmployees) . ' before deactivating it.'
        : null;
@endphp
<x-layouts.app :title="'Branch · ' . $branch->name">
    <x-slot:header>
        <x-ui.page-header :title="$branch->name" :subtitle="$branch->code . ' · ' . $activeEmployees . ' active ' . \Illuminate\Support\Str::plural('employee', $activeEmployees)">
            <x-ui.badge :variant="$branch->is_active ? 'green' : 'gray'">{{ $branch->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
        </x-ui.page-header>
    </x-slot:header>

    @if ($readOnly)
        <div role="status" class="mb-6 rounded-xl bg-slate-100 p-4 text-sm text-slate-700 ring-1 ring-slate-200">You can view this branch but not change it.</div>
    @endif

    <div class="max-w-3xl space-y-6">
        @include('organization.branches._form', ['branch' => $branch, 'readOnly' => $readOnly])

        @can('deactivate', $branch)
            @include('organization.partials.status', [
                'active' => $branch->is_active,
                'action' => route('organization.branches.status.update', $branch),
                'noun' => 'branch',
                'blockReason' => $blockReason,
                'deactivateNote' => "New employees can't be assigned to it. Its history is kept.",
            ])
        @endcan
    </div>
</x-layouts.app>
