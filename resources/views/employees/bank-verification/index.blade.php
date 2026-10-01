<x-layouts.app title="Bank verification">
    <x-slot:header><x-ui.page-header title="Bank verification" subtitle="Pending bank accounts waiting for a reviewer." /></x-slot:header>

    <x-ui.card>
        <div class="-mx-5 -my-5">
            @if ($accounts->isEmpty())
                <x-ui.empty-state title="Nothing to verify" description="New submissions will show up here." icon="banknotes" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        @foreach (['Employee', 'Bank', 'Account name', 'Number', 'Submitted by', 'Submitted', 'Actions'] as $h)
                            <th scope="col" class="px-5 py-3 font-medium">{{ $h }}</th>
                        @endforeach
                    </x-slot:head>
                    @foreach ($accounts as $a)
                        @php $emp = $a->employee; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                @can('view', $emp)
                                    <a href="{{ route('employees.show', $emp) }}#bank" class="font-medium text-brand-700 hover:underline">{{ $emp->last_name }}, {{ $emp->first_name }}</a>
                                @else
                                    <span class="font-medium">{{ $emp->last_name }}, {{ $emp->first_name }}</span>
                                @endcan
                                <p class="font-mono text-xs text-slate-500">{{ $emp->employee_number }}</p>
                            </td>
                            <td class="px-5 py-3">{{ $a->bank_name }}</td>
                            <td class="px-5 py-3">{{ $a->account_name }}</td>
                            <td class="px-5 py-3">
                                <x-masked-field compact :masked="$a->masked()" field="bank_account" :account-id="$a->id"
                                                :url="route('employees.reveal', $emp)" :can-reveal="auth()->user()->can('revealBank', $emp)" />
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $a->submitter?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">{{ $a->created_at->format('M j, Y g:i A') }}</td>
                            <td class="px-5 py-3">@include('employees.partials.bank-review-actions', ['account' => $a, 'employee' => $emp])</td>
                        </tr>
                    @endforeach
                </x-ui.table>
                @if ($accounts->hasPages())<div class="border-t border-slate-100 px-5 py-3">{{ $accounts->links() }}</div>@endif
            @endif
        </div>
    </x-ui.card>
</x-layouts.app>