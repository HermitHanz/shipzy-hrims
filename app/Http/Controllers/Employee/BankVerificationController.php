<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Actions\Employees\ReviewBankAccount;
use App\Http\Requests\Employees\ReviewBankAccountRequest;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BankVerificationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('employee.bank.verify');

        // Same scope as the employee list: reviewers only see employees they can view
        $accounts = EmployeeBankAccount::query()
            ->where('status', 'pending')
            ->whereIn('employee_id', Employee::visibleTo($request->user(), 'employee.record.view')->select('id'))
            ->with(['employee.user', 'submitter'])
            ->oldest()
            ->paginate(15);

        return view('employees.bank-verification.index', compact('accounts'));
    }

    public function review(ReviewBankAccountRequest $request, Employee $employee, EmployeeBankAccount $bankAccount, ReviewBankAccount $action): RedirectResponse
    {
        Gate::authorize('verifyBank', $employee);

        $decision = $request->validated('decision');
        $action->handle($request->user(), $bankAccount, $decision, $request->validated('reason'));

        return redirect()->back()->withFragment('bank')
            ->with('status', $decision === 'verify' ? 'Bank account verified.' : 'Bank account rejected.');
    }
}