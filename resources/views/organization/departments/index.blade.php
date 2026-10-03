<x-layouts.app title="Departments">
    @error('department')
        <div role="alert" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">{{ $message }}</div>
    @enderror
    <x-slot:header>
        <x-ui.page-header title="Departments" subtitle="Teams employees belong to. Departments can be nested.">
            @can('create', \App\Models\Department::class)
                <x-ui.button :href="route('organization.departments.create')"><x-ui.icon name="plus" class="size-4" /> New department</x-ui.button>
            @endcan
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card>
        <div class="-mx-5 -my-5">
            @if ($departments->isEmpty())
                <x-ui.empty-state title="No departments yet" description="Create a department before adding employees." icon="rectangle-group" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        <th scope="col" class="px-5 py-3 font-medium">Department</th>
                        <th scope="col" class="px-5 py-3 font-medium">Code</th>
                        <th scope="col" class="px-5 py-3 font-medium">Head</th>
                        <th scope="col" class="px-5 py-3 font-medium">Active employees</th>
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 font-medium">Actions</th>
                    </x-slot:head>

                    @foreach ($departments as $department)
                        @php
                            $editable    = auth()->user()->can('update', $department);
                            $blockReason = $department->employee_records_count > 0 ? 'This department has employee records. Deactivate it instead.'
                                : ($department->children_count > 0 ? 'Remove or move its sub-departments first.' : null);
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <span class="font-medium">{{ $department->name }}</span>
                                @if ($department->parent)<p class="mt-0.5 text-xs text-slate-500">Under {{ $department->parent->name }}</p>@endif
                            </td>
                            <td class="px-5 py-3"><code class="text-xs text-slate-600">{{ $department->code }}</code></td>
                            <td class="px-5 py-3 text-slate-600">{{ $department->head ? $department->head->last_name . ', ' . $department->head->first_name : '—' }}</td>
                            <td class="px-5 py-3">{{ $department->active_employees_count }}</td>
                            <td class="px-5 py-3"><x-ui.badge :variant="$department->is_active ? 'green' : 'gray'">{{ $department->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    @can('view', $department)
                                        <x-ui.button variant="secondary" :href="route('organization.departments.edit', $department)" class="!px-3 !py-1.5">
                                            <x-ui.icon name="{{ $editable ? 'pencil' : 'eye' }}" class="size-4" /> {{ $editable ? 'Edit' : 'View' }}
                                        </x-ui.button>
                                    @endcan
                                    @can('delete', $department)
                                        @if ($blockReason)
                                            <button type="button" disabled aria-disabled="true" title="{{ $blockReason }}"
                                                    class="inline-flex cursor-not-allowed items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200">
                                                <x-ui.icon name="trash" class="size-4" /> <span class="sr-only">Delete unavailable: {{ $blockReason }}</span><span aria-hidden="true">Delete</span>
                                            </button>
                                        @else
                                            <x-ui.button variant="danger" class="!px-3 !py-1.5" x-on:click="$dispatch('open-modal', 'delete-department-{{ $department->id }}')">
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

    @foreach ($departments as $department)
        @if ((int) $department->employee_records_count === 0 && (int) $department->children_count === 0)
            @can('delete', $department)
                <x-ui.modal :name="'delete-department-' . $department->id" :title="'Delete “' . $department->name . '”?'" description="Only for departments created by mistake. This can't be undone.">
                    <form method="POST" action="{{ route('organization.departments.destroy', $department) }}" class="flex justify-end gap-2">
                        @csrf @method('DELETE')
                        <x-ui.button variant="secondary" data-autofocus x-on:click="hide()">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Delete department</x-ui.button>
                    </form>
                </x-ui.modal>
            @endcan
        @endif
    @endforeach
</x-layouts.app>
