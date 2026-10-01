@php
    $statusVariant = ['active' => 'green', 'inactive' => 'gray', 'suspended' => 'red'];
    $canManage     = auth()->user()->can('manageAccess', $user);
    $employeeName  = $user->employee ? trim($user->employee->first_name . ' ' . $user->employee->last_name) : null;
    $statusActions = [
        'active'    => ['button' => 'Activate',   'title' => 'Activate this account?',   'body' => 'They will be able to sign in again.'],
        'inactive'  => ['button' => 'Deactivate', 'title' => 'Deactivate this account?', 'body' => 'They will no longer be able to sign in. Their data is kept.'],
        'suspended' => ['button' => 'Suspend',    'title' => 'Suspend this account?',    'body' => 'Sign-in is blocked until the account is reactivated.'],
    ];
@endphp

<x-layouts.app :title="'Access · ' . $user->name">
    <x-slot:header>
        <x-ui.page-header :title="$user->name" subtitle="Roles, permissions and account controls.">
            <x-ui.button variant="secondary" :href="route('admin.users.index')">Back to users</x-ui.button>
        </x-ui.page-header>
    </x-slot:header>

    @unless ($canManage)
        <div role="status" class="mb-6 rounded-xl bg-slate-100 p-4 text-sm text-slate-700 ring-1 ring-slate-200">
            You can view this account but not change it. It's your own account, or belongs to someone at or above your level.
        </div>
    @endunless

    <div class="space-y-6">
        {{-- 1. Summary --}}
        <x-ui.card title="Account">
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-slate-500">Email</dt><dd class="mt-0.5 font-medium">{{ $user->email }}</dd></div>
                <div><dt class="text-slate-500">Employee</dt><dd class="mt-0.5 font-medium">{{ $employeeName ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="mt-0.5"><x-ui.badge :variant="$statusVariant[$user->status] ?? 'gray'">{{ ucfirst($user->status) }}</x-ui.badge></dd></div>
                <div><dt class="text-slate-500">Last login</dt>
                    <dd class="mt-0.5 font-medium">{{ $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at)->diffForHumans() : 'Never' }}</dd></div>
            </dl>
        </x-ui.card>

        {{-- 2. Roles --}}
        <x-ui.card title="Roles">
            <form method="POST" action="{{ route('admin.users.roles.update', $user) }}">
                @csrf @method('PUT')
                @foreach (['roles', 'role'] as $key)
                    @error($key)<p class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
                @endforeach

                @include('admin.users._roles-list', [
                    'roles'    => $roleOptions,
                    'selected' => old('roles', $userRoleNames),
                    'readonly' => ! $canManage,
                ])

                @if ($canManage)
                    <div class="mt-4 flex justify-end"><x-ui.button type="submit">Save roles</x-ui.button></div>
                @endif
            </form>
        </x-ui.card>

        {{-- 3. Direct permissions --}}
        <x-ui.card title="Direct permissions">
            <p class="mb-4 text-sm text-slate-500">Extra access granted straight to this person, on top of what their roles provide. Role permissions appear checked and locked.</p>
            <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
                @csrf @method('PUT')
                @error('permissions')<p class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror

                <x-permission-matrix :granted="old('permissions', $direct)" :inherited="$inherited" :readonly="! $canManage" />

                @if ($canManage)
                    <div class="mt-4 flex justify-end"><x-ui.button type="submit">Save permissions</x-ui.button></div>
                @endif
            </form>
        </x-ui.card>

        {{-- 4. Effective access --}}
        <x-ui.card title="Effective access">
            <p class="mb-4 text-sm text-slate-500">Everything this person can do right now, and where it comes from.</p>
            <div class="space-y-2">
                @forelse ($effectiveByModule as $module => $items)
                    <details class="rounded-lg ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between px-4 py-2.5 text-sm font-medium">
                            {{ \Illuminate\Support\Str::headline($module) }}
                            <span class="text-xs font-normal text-slate-500">{{ $items->count() }} permissions</span>
                        </summary>
                        <ul class="divide-y divide-slate-100 border-t border-slate-100">
                            @foreach ($items as $key => $info)
                                <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
                                    <code class="text-xs text-slate-700">{{ $key }}</code>
                                    <span class="flex flex-wrap gap-1">
                                        @foreach ($info['roles'] as $roleLabel)
                                            <x-ui.badge variant="brand">via {{ $roleLabel }}</x-ui.badge>
                                        @endforeach
                                        @if ($info['direct'])<x-ui.badge variant="green">direct</x-ui.badge>@endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @empty
                    <x-ui.empty-state title="No permissions" description="This account has no roles or direct permissions." icon="shield" />
                @endforelse
            </div>
        </x-ui.card>

        {{-- 5. Status --}}
        @can('deactivate', $user)
            <x-ui.card title="Account status">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p class="text-sm text-slate-600">Currently <x-ui.badge :variant="$statusVariant[$user->status] ?? 'gray'">{{ ucfirst($user->status) }}</x-ui.badge></p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($statusActions as $status => $meta)
                            @if ($status !== $user->status)
                                <x-ui.button :variant="$status === 'active' ? 'secondary' : 'danger'"
                                             x-on:click="$dispatch('open-modal', 'status-{{ $status }}')">{{ $meta['button'] }}</x-ui.button>
                            @endif
                        @endforeach
                    </div>
                </div>
                @error('status')<p class="mt-3 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
            </x-ui.card>
        @endcan

        {{-- 6. Password --}}
        @can('update', $user)
            <x-ui.card title="Password">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p class="text-sm text-slate-600">Generate a new temporary password. They'll have to change it at next sign-in.</p>
                    <x-ui.button variant="secondary" x-on:click="$dispatch('open-modal', 'reset-password')">
                        <x-ui.icon name="key" class="size-4" /> Reset password
                    </x-ui.button>
                </div>
            </x-ui.card>
        @endcan
    </div>

    {{-- Modals --}}
    @can('deactivate', $user)
        @foreach ($statusActions as $status => $meta)
            @if ($status !== $user->status)
                <x-ui.modal :name="'status-' . $status" :title="$meta['title']" :description="$meta['body']">
                    <form method="POST" action="{{ route('admin.users.status.update', $user) }}" class="flex justify-end gap-2">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status }}">
                        <x-ui.button variant="secondary" data-autofocus x-on:click="hide()">Cancel</x-ui.button>
                        <x-ui.button type="submit" :variant="$status === 'active' ? 'primary' : 'danger'">{{ $meta['button'] }}</x-ui.button>
                    </form>
                </x-ui.modal>
            @endif
        @endforeach
    @endcan

    @can('update', $user)
        <x-ui.modal name="reset-password" title="Reset password?" description="The current password stops working immediately. You'll see the new temporary password once.">
            <form method="POST" action="{{ route('admin.users.password.reset', $user) }}" class="flex justify-end gap-2">
                @csrf
                <x-ui.button variant="secondary" data-autofocus x-on:click="hide()">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="danger">Reset password</x-ui.button>
            </form>
        </x-ui.modal>
    @endcan
</x-layouts.app>