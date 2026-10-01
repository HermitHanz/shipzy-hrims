{{-- $account, $employee --}}
@php
    $me = auth()->user();
    $blocked = (int) $account->submitted_by === (int) $me->id
        || ($me->employee_id && (int) $me->employee_id === (int) $account->employee_id);
    $disabledBtn = 'inline-flex cursor-not-allowed items-center rounded-lg px-3 py-1.5 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200';
@endphp
@if ($account->status === 'pending')
    @can('verifyBank', $employee)
        @if ($blocked)
            <div class="flex gap-2" title="Needs another reviewer">
                <button type="button" disabled aria-disabled="true" class="{{ $disabledBtn }}">Verify</button>
                <button type="button" disabled aria-disabled="true" class="{{ $disabledBtn }}">Reject</button>
                <span class="sr-only">Needs another reviewer</span>
            </div>
        @else
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('employees.bank-accounts.review', [$employee, $account]) }}">
                    @csrf <input type="hidden" name="decision" value="verify">
                    <x-ui.button type="submit" class="!px-3 !py-1.5">Verify</x-ui.button>
                </form>
                <x-ui.button variant="danger" class="!px-3 !py-1.5" x-on:click="$dispatch('open-modal', 'reject-{{ $account->id }}')">Reject</x-ui.button>
            </div>

            <x-ui.modal :name="'reject-' . $account->id" title="Reject this bank account?" description="The employee will see your reason and can submit a new account.">
                <form method="POST" action="{{ route('employees.bank-accounts.review', [$employee, $account]) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="decision" value="reject">
                    <input type="hidden" name="reject_for" value="{{ $account->id }}">
                    @php $mine = (string) old('reject_for') === (string) $account->id; @endphp
                    <div>
                        <label for="reason-{{ $account->id }}" class="mb-1 block text-sm font-medium text-slate-700">Reason</label>
                        <textarea id="reason-{{ $account->id }}" name="reason" rows="3" required data-autofocus
                                  @if ($mine && $errors->has('reason')) aria-invalid="true" @endif
                                  class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-brand-500 {{ $mine && $errors->has('reason') ? 'ring-red-500' : 'ring-slate-300' }}">{{ $mine ? old('reason') : '' }}</textarea>
                        @if ($mine) @error('reason')<p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror @endif
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.button variant="secondary" x-on:click="hide()">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="danger">Reject account</x-ui.button>
                    </div>
                </form>
            </x-ui.modal>

            @if ($mine && $errors->has('reason'))
                <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'reject-{{ $account->id }}'))"></div>
            @endif
        @endif
    @endcan
@endif