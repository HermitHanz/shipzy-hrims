<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kicks out accounts that were deactivated or suspended, even mid-session.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $this->mayAccess($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not active. Please contact HR.']);
        }

        return $next($request);
    }

    private function mayAccess(User $user): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        // Safety net: a separated employee never has access, even if their login was left active
        // (for example after a direct database edit or an import that skipped the status action).
        return ! ($user->employee_id
            && Employee::whereKey($user->employee_id)->where('status', 'separated')->exists());
    }
}