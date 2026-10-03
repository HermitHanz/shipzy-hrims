{{-- $employee, $bankAccounts, $storeAction, $showReview --}}
@php
    $current    = $employee->currentBankAccount;
    $canReveal  = auth()->user()->can('revealBank', $employee);
    $revealUrl  = route('employees.reveal', $employee);
    $hasPending = $bankAccounts->contains('status', 'pending');
@endphp
<div class="space-y-6">
    <x-ui.card title="Payroll account">
        @if ($current)
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Bank</dt><dd class="mt-0.5 font-medium">{{ $current->bank_name }}</dd>@if ($current->accountTypeLabel())<dd class="text-xs text-slate-500">{{ $current->accountTypeLabel() }}</dd>@endif</div>
                <div><dt class="text-slate-500">Account name</dt><dd class="mt-0.5 font-medium">{{ $current->account_name }}</dd></div>
                <div><x-masked-field label="Account number" :masked="$current->masked()" field="bank_account" :account-id="$current->id" :url="$revealUrl" :can-reveal="$canReveal" /></div>
            </dl>
        @else
            <p class="text-sm text-slate-500">No verified account yet. Payroll can't pay out until one is verified.</p>
        @endif
        @if ($hasPending)
            <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">A new account is awaiting verification. Payroll keeps using the verified account above until then.</p>
        @endif
        @error('decision')<p class="mt-3 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
    </x-ui.card>

    <x-ui.card title="Submissions">
        <div class="-mx-5 -my-5">
            @if ($bankAccounts->isEmpty())
                <x-ui.empty-state title="No bank accounts submitted" icon="banknotes" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        @foreach (['Bank / account name', 'Number', 'Status', 'Submitted', 'Reviewed', ($showReview ? 'Actions' : '')] as $h)
                            <th scope="col" class="px-5 py-3 font-medium">{{ $h }}</th>
                        @endforeach
                    </x-slot:head>
                    @foreach ($bankAccounts as $a)
                        <tr>
                            <td class="px-5 py-3"><p class="font-medium">{{ $a->bank_name }}</p><p class="text-xs text-slate-500">{{ $a->account_name }}@if ($a->accountTypeLabel()) · {{ $a->accountTypeLabel() }}@endif</p></td>
                            <td class="px-5 py-3">
                                <x-masked-field compact :masked="$a->masked()" field="bank_account" :account-id="$a->id" :url="$revealUrl" :can-reveal="$canReveal && in_array($a->status, ['pending', 'verified'])" />
                            </td>
                            <td class="px-5 py-3">
                                <x-status-badge type="bank" :status="$a->status" />
                                @if ($a->status === 'pending' && ! $showReview)<p class="mt-1 text-xs text-slate-500">Awaiting HR verification</p>@endif
                                @if ($a->status === 'rejected' && $a->rejection_reason)<p class="mt-1 max-w-xs text-xs text-red-600">{{ $a->rejection_reason }}</p>@endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $a->created_at->format('M j, Y') }}@if ($showReview && $a->submitter)<br>by {{ $a->submitter->name }}@endif</td>
                            <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
                                @if ($a->reviewed_at){{ $a->reviewed_at->format('M j, Y') }}@if ($showReview && $a->reviewer)<br>by {{ $a->reviewer->name }}@endif @else — @endif
                            </td>
                            @if ($showReview)
                                <td class="px-5 py-3">@include('employees.partials.bank-review-actions', ['account' => $a, 'employee' => $employee])</td>
                            @endif
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>
    </x-ui.card>

    @can('updateBank', $employee)
        <x-ui.card title="Submit a bank account">
            <p class="mb-4 text-sm text-slate-500">New accounts stay <strong>pending</strong>. Payroll keeps using the verified account until someone else verifies the new one.</p>
            <form method="POST" action="{{ $storeAction }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                <x-ui.input name="bank_name" label="Bank" required />
                <x-ui.select name="account_type" label="Account type" placeholder="Choose…" required>
                    @foreach (\App\Models\EmployeeBankAccount::ACCOUNT_TYPES as $v => $l)<option value="{{ $v }}" @selected(old('account_type') === $v)>{{ $l }}</option>@endforeach
                </x-ui.select>
                <x-ui.input name="account_name" label="Account name" required />
                <x-ui.input name="account_number" label="Account number" sensitive required />
                <div class="sm:col-span-2 flex justify-end"><x-ui.button type="submit">Submit account</x-ui.button></div>
            </form>
        </x-ui.card>
    @endcan
</div>