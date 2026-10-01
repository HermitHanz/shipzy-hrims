<x-layouts.app title="Roles">
    @error('role')
        <div role="alert" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">{{ $message }}</div>
    @enderror
    <x-slot:header>
        <x-ui.page-header title="Roles & permissions" subtitle="Define what each role can do.">
            @can('create', \Spatie\Permission\Models\Role::class)
                <x-ui.button :href="route('admin.roles.create')"><x-ui.icon name="plus" class="size-4" /> New role</x-ui.button>
            @endcan
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card>
        <div class="-mx-5 -my-5">
            @if ($roles->isEmpty())
                <x-ui.empty-state title="No roles yet" icon="shield" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        <th scope="col" class="px-5 py-3 font-medium">Role</th>
                        <th scope="col" class="px-5 py-3 font-medium">Name</th>
                        <th scope="col" class="px-5 py-3 font-medium">Level</th>
                        <th scope="col" class="px-5 py-3 font-medium">Users</th>
                        <th scope="col" class="px-5 py-3 font-medium">Permissions</th>
                        <th scope="col" class="px-5 py-3 font-medium">Actions</th>
                    </x-slot:head>

                    @foreach ($roles as $role)
                        @php
                            $editable    = $role->name !== 'super-admin' && auth()->user()->can('update', $role);
                            $blockReason = $role->is_system ? 'System roles cannot be deleted.'
                                : ($role->users_count > 0 ? "Reassign its {$role->users_count} user(s) before deleting." : null);
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">{{ $role->label ?: $role->name }}</span>
                                    @if ($role->is_system)<x-ui.badge variant="brand">System</x-ui.badge>@endif
                                </div>
                                @if ($role->description)<p class="mt-0.5 max-w-sm truncate text-xs text-slate-500">{{ $role->description }}</p>@endif
                            </td>
                            <td class="px-5 py-3"><code class="text-xs text-slate-600">{{ $role->name }}</code></td>
                            <td class="px-5 py-3">{{ $role->level }}</td>
                            <td class="px-5 py-3">{{ $role->users_count }}</td>
                            <td class="px-5 py-3">{{ $role->permissions_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    @can('view', $role)
                                        <x-ui.button variant="secondary" :href="route('admin.roles.edit', $role)" class="!px-3 !py-1.5">
                                            <x-ui.icon name="{{ $editable ? 'pencil' : 'eye' }}" class="size-4" /> {{ $editable ? 'Edit' : 'View' }}
                                        </x-ui.button>
                                    @endcan
                                    @can('delete', $role)
                                        @if ($blockReason)
                                            <button type="button" disabled aria-disabled="true" title="{{ $blockReason }}"
                                                    class="inline-flex cursor-not-allowed items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200">
                                                <x-ui.icon name="trash" class="size-4" /> <span class="sr-only">Delete unavailable: {{ $blockReason }}</span><span aria-hidden="true">Delete</span>
                                            </button>
                                        @else
                                            <x-ui.button variant="danger" class="!px-3 !py-1.5" x-on:click="$dispatch('open-modal', 'delete-role-{{ $role->id }}')">
                                                <x-ui.icon name="trash" class="size-4" /> Delete
                                            </x-ui.button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>
    </x-ui.card>

    @foreach ($roles as $role)
        @if (! $role->is_system && $role->users_count === 0)
            @can('delete', $role)
                <x-ui.modal :name="'delete-role-' . $role->id" :title="'Delete “' . ($role->label ?: $role->name) . '”?'" description="This removes the role and its permission set. It can't be undone.">
                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="flex justify-end gap-2">
                        @csrf @method('DELETE')
                        <x-ui.button variant="secondary" data-autofocus x-on:click="hide()">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Delete role</x-ui.button>
                    </form>
                </x-ui.modal>
            @endcan
        @endif
    @endforeach
</x-layouts.app>