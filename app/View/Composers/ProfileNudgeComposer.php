<?php

namespace App\View\Composers;

use App\Models\EmployeeGovernmentId;
use Illuminate\View\View;

class ProfileNudgeComposer
{
    public function compose(View $view): void
    {
        $user     = auth()->user();
        $employee = $user?->employee;
        $missing  = [];

        if ($employee && $employee->onboarding_completed_at && $user->can('employee.record.view-own')) {
            $gov = $employee->governmentId;
            foreach (EmployeeGovernmentId::TYPES as $type) {
                if (! $gov || ! $gov->isSet($type)) {
                    $missing[] = (EmployeeGovernmentId::LABELS[$type] ?? strtoupper($type)) . ' number';
                }
            }
            if (! $employee->emergencyContacts()->exists()) {
                $missing[] = 'Emergency contact';
            }
            if (! $employee->bankAccounts()->whereIn('status', ['pending', 'verified'])->exists()) {
                $missing[] = 'Bank account';
            }
        }

        $view->with('profileNudge', $missing);
    }
}