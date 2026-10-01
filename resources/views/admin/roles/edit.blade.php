<x-layouts.app :title="'Role · ' . ($role->label ?: $role->name)">
    <x-slot:header>
        <x-ui.page-header :title="$role->label ?: $role->name" :subtitle="$usersCount . ' ' . \Illuminate\Support\Str::plural('user', $usersCount) . ' assigned'">
            @if ($role->is_system)<x-ui.badge variant="brand">System role</x-ui.badge>@endif
        </x-ui.page-header>
    </x-slot:header>

    @if ($readOnly)
        <div role="status" class="mb-6 rounded-xl bg-slate-100 p-4 text-sm text-slate-700 ring-1 ring-slate-200">
            {{ $role->name === 'super-admin' ? 'The Super Admin role is view-only.' : "You can view this role but not change it." }}
        </div>
    @endif

    <div class="max-w-5xl">
        @include('admin.roles._form', ['levels' => $levels, 'role' => $role, 'readOnly' => $readOnly, 'granted' => $granted])
    </div>
</x-layouts.app>