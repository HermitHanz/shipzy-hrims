<?php

namespace App\Http\Controllers;

use App\Events\ModuleUnlockAttempted;
use App\Http\Requests\UnlockModuleRequest;
use App\Support\Security\ModuleUnlock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class ModuleUnlockController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 300;

    public function __construct(private ModuleUnlock $unlock) {}

    public function show(Request $request, string $module): View|RedirectResponse
    {
        $def = $this->moduleFor($request, $module);

        if ($this->unlock->isUnlocked($request->user(), $module)) {
            return redirect()->to($this->destination($request, $module, $def));
        }

        return view('module-unlock.show', compact('module', 'def'));
    }

    public function store(UnlockModuleRequest $request, string $module): RedirectResponse
    {
        $def  = $this->moduleFor($request, $module);
        $user = $request->user();
        $key  = 'module-unlock:' . $user->getAuthIdentifier() . ':' . $module;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            event(new ModuleUnlockAttempted($user, $module, false, $request->ip(), throttled: true));

            throw ValidationException::withMessages([
                'password' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        if (! Hash::check($request->validated('password'), $user->getAuthPassword())) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            event(new ModuleUnlockAttempted($user, $module, false, $request->ip()));

            throw ValidationException::withMessages(['password' => 'That password is incorrect.']);
        }

        RateLimiter::clear($key);
        $this->unlock->unlock($user, $module);
        event(new ModuleUnlockAttempted($user, $module, true, $request->ip()));

        return redirect()->to($this->destination($request, $module, $def));
    }

    public function destroy(Request $request, string $module): RedirectResponse
    {
        $def = $this->moduleFor($request, $module);
        $this->unlock->lock($module);

        return to_route('dashboard')->with('status', "{$def['label']} locked.");
    }

    /** 404 for unknown modules, 403 if the user holds none of the module's permissions. */
    private function moduleFor(Request $request, string $module): array
    {
        $def = $this->unlock->find($module) ?? abort(404);

        abort_if(! empty($def['permissions']) && ! $request->user()->canAny($def['permissions']), 403);

        return $def;
    }

    private function destination(Request $request, string $module, array $def): string
    {
        $intended = $request->session()->pull("module_unlock_intended.{$module}");

        if ($intended) {
            return $intended;
        }

        return ($def['home'] && Route::has($def['home'])) ? route($def['home']) : route('dashboard');
    }
}