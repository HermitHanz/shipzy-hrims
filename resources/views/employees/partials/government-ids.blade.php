{{-- $employee, $action --}}
@php
    $gov       = $employee->governmentId;
    $types     = \App\Models\EmployeeGovernmentId::TYPES;
    $canEdit   = auth()->user()->can('updateGovernmentIds', $employee);
    $canReveal = auth()->user()->can('revealGovernmentId', $employee);
    $errored   = collect($types)->filter(fn ($t) => $errors->has($t))->values()->all();
@endphp
<x-ui.card title="Government IDs">
    <p class="mb-4 text-sm text-slate-500">Numbers stay masked. Only what you change or remove here is saved.</p>
    <form method="POST" action="{{ $action }}" x-data="governmentIds(@js($types), @js($errored))">
        @csrf @method('PUT')
        <div class="divide-y divide-slate-100">
            @foreach ($types as $type)
                @php $isSet = $gov && $gov->isSet($type); $label = \App\Models\EmployeeGovernmentId::LABELS[$type] ?? strtoupper($type); @endphp
                <div class="py-4 first:pt-0 last:pb-0">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            @if ($isSet)
                                <x-masked-field :label="$label" :masked="$gov->masked($type)" :field="$type" :url="route('employees.reveal', $employee)"
                                                :can-reveal="$canReveal" x-show="mode.{{ $type }} === 'idle'" />
                            @else
                                <div x-show="mode.{{ $type }} === 'idle'">
                                    <p class="text-sm text-slate-500">{{ $label }}</p>
                                    <p class="mt-0.5 text-sm text-slate-400">Not on file</p>
                                </div>
                            @endif

                            <template x-if="mode.{{ $type }} === 'change'">
                                <div class="max-w-sm">
                                    <x-ui.input :name="$type" :label="$label . ' number'" sensitive x-ref="{{ $type }}" />
                                </div>
                            </template>
                            <template x-if="mode.{{ $type }} === 'remove'">
                                <div>
                                    <p class="text-sm text-slate-500">{{ $label }}</p>
                                    <p class="mt-0.5 text-sm font-medium text-red-600">Will be removed when you save</p>
                                    <input type="hidden" name="{{ $type }}" value="">
                                </div>
                            </template>
                        </div>

                        @if ($canEdit)
                            <div class="flex gap-2">
                                <template x-if="mode.{{ $type }} === 'idle'">
                                    <div class="flex gap-2">
                                        <x-ui.button variant="secondary" class="!px-3 !py-1.5" x-on:click="set('{{ $type }}', 'change')">{{ $isSet ? 'Change' : 'Add' }}</x-ui.button>
                                        @if ($isSet)<x-ui.button variant="secondary" class="!px-3 !py-1.5" x-on:click="set('{{ $type }}', 'remove')">Remove</x-ui.button>@endif
                                    </div>
                                </template>
                                <template x-if="mode.{{ $type }} !== 'idle'">
                                    <x-ui.button variant="secondary" class="!px-3 !py-1.5" x-on:click="set('{{ $type }}', 'idle')">{{ '' }}Undo</x-ui.button>
                                </template>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($canEdit)
            <div class="mt-4 flex justify-end">
                <x-ui.button type="submit" x-bind:disabled="! dirty">Save changes</x-ui.button>
            </div>
        @endif
    </form>
</x-ui.card>