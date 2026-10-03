<x-layouts.app title="Branches">
    @error('branch')
        <div role="alert" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">{{ $message }}</div>
    @enderror
    <x-slot:header>
        <x-ui.page-header title="Branches" subtitle="Office locations employees are assigned to.">
            @can('create', \App\Models\Branch::class)
                <x-ui.button :href="route('organization.branches.create')"><x-ui.icon name="plus" class="size-4" /> New branch</x-ui.button>
            @endcan
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card>
        <div class="-mx-5 -my-5">
            @if ($branches->isEmpty())
                <x-ui.empty-state title="No branches yet" description="Create a branch before adding employees." icon="building-office" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        <th scope="col" class="px-5 py-3 font-medium">Branch</th>
                        <th scope="col" class="px-5 py-3 font-medium">Code</th>
                        <th scope="col" class="px-5 py-3 font-medium">Active employees</th>
                        <th scope="col" class="px-5 py-3 font-medium">Status</th>
                        <th scope="col" class="px-5 py-3 font-medium">Actions</th>
                    </x-slot:head>

                    @foreach ($branches as $branch)
                        @php
                            $editable    = auth()->user()->can('update', $branch);
                            $blockReason = $branch->employee_records_count > 0 ? 'This branch has employee records. Deactivate it instead.' : null;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <span class="font-medium">{{ $branch->name }}</span>
                                @if ($branch->address)<p class="mt-0.5 max-w-sm truncate text-xs text-slate-500">{{ $branch->address }}</p>@endif
                            </td>
                            <td class="px-5 py-3"><code class="text-xs text-slate-600">{{ $branch->code }}</code></td>
                            <td class="px-5 py-3">{{ $branch->active_employees_count }}</td>
                            <td class="px-5 py-3"><x-ui.badge :variant="$branch->is_active ? 'green' : 'gray'">{{ $branch->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    @can('view', $branch)
                                        <x-ui.button variant="secondary" :href="route('organization.branches.edit', $branch)" class="!px-3 !py-1.5">
                                            <x-ui.icon name="{{ $editable ? 'pencil' : 'eye' }}" class="size-4" /> {{ $editable ? 'Edit' : 'View' }}
                                        </x-ui.button>
                                    @endcan
                                    @can('delete', $branch)
                                        @if ($blockReason)
                                            <button type="button" disabled aria-disabled="true" title="{{ $blockReason }}"
                                                    class="inline-flex cursor-not-allowed items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200">
                                                <x-ui.icon name="trash" class="size-4" /> <span class="sr-only">Delete unavailable: {{ $blockReason }}</span><span aria-hidden="true">Delete</span>
                                            </button>
                                        @else
                                            <x-ui.button variant="danger" class="!px-3 !py-1.5" x-on:click="$dispatch('open-modal', 'delete-branch-{{ $branch->id }}')">
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

    @foreach ($branches as $branch)
        @if ((int) $branch->employee_records_count === 0)
            @can('delete', $branch)
                <x-ui.modal :name="'delete-branch-' . $branch->id" :title="'Delete “' . $branch->name . '”?'" description="Only for branches created by mistake. This can't be undone.">
                    <form method="POST" action="{{ route('organization.branches.destroy', $branch) }}" class="flex justify-end gap-2">
                        @csrf @method('DELETE')
                        <x-ui.button variant="secondary" data-autofocus x-on:click="hide()">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Delete branch</x-ui.button>
                    </form>
                </x-ui.modal>
            @endcan
        @endif
    @endforeach
</x-layouts.app>
