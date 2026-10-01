<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Employees must complete their required profile details before using anything else.
 * Runs after RequirePasswordChange, so the order is: new password first, then onboarding.
 *
 * Only applies to accounts linked to an employee record whose role lets them edit their own
 * personal details (otherwise they could never finish and would be locked out).
 */
class RequireOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->employee_id
            && ! $user->must_change_password
            && ! $request->session()->get('onboarding_done')
            && ! $request->routeIs('onboarding.*', 'password.change', 'password.change.update', 'logout')
            && $user->can('employee.personal.edit-own')
        ) {
            $done = Employee::whereKey($user->employee_id)->whereNotNull('onboarding_completed_at')->exists();

            if (! $done) {
                return redirect()->route('onboarding.edit');
            }

            $request->session()->put('onboarding_done', true);
        }

        return $next($request);
    }
}