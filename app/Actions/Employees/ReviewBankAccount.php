<?php

namespace App\Actions\Employees;

use App\Models\EmployeeBankAccount;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ReviewBankAccount extends EmployeeAction
{
    /**
     * Segregation of duties: you can't review your own account, and you can't review a
     * submission you made yourself (for example when HR entered it on someone's behalf).
     * Verifying supersedes the previously verified account.
     */
    public function handle(User $actor, EmployeeBankAccount $account, string $decision, ?string $reason = null): EmployeeBankAccount
    {
        $employee = $account->employee;

        $this->authorize($actor, 'verifyBank', $employee);

        if ($account->submitted_by !== null && $account->submitted_by === $actor->id) {
            throw new AuthorizationException('You cannot review a bank account you submitted.');
        }

        if (! in_array($decision, ['verify', 'reject'], true)) {
            $this->reject('decision', 'Invalid decision.');
        }

        if ($account->status !== EmployeeBankAccount::PENDING) {
            $this->reject('account', 'This submission has already been reviewed.');
        }

        $reason = $reason !== null ? trim($reason) : null;

        if ($decision === 'reject' && ($reason === null || $reason === '')) {
            $this->reject('reason', 'Give a reason for rejecting.');
        }

        DB::transaction(function () use ($actor, $employee, $account, $decision, $reason) {
            if ($decision === 'verify') {
                $employee->bankAccounts()
                    ->where('status', EmployeeBankAccount::VERIFIED)
                    ->update(['status' => EmployeeBankAccount::SUPERSEDED]);
            }

            $account->forceFill([
                'status' => $decision === 'verify' ? EmployeeBankAccount::VERIFIED : EmployeeBankAccount::REJECTED,
                'rejection_reason' => $decision === 'reject' ? $reason : null,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ])->save();

            $this->audit->log('employee.bank_account_reviewed', $employee, null, [
                'decision' => $decision,
                'last4' => $account->account_last4,
            ], $actor);
        });

        return $account->refresh();
    }
}