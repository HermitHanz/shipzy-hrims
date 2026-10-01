<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Actions\Employees\CompleteOnboarding;
use App\Http\Requests\Employees\OnboardingRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OnboardingController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $employee = $request->user()->employee;

        if (! $employee || $employee->onboarding_completed_at) {
            return to_route('dashboard');
        }

        Gate::authorize('updatePersonal', $employee);

        return view('me.onboarding', ['employee' => $employee->load('personalDetail')]);
    }

    public function update(OnboardingRequest $request, CompleteOnboarding $action): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);
        Gate::authorize('updatePersonal', $employee);

        $action->handle($request->user(), $employee, [...$request->validated(), 'accept_privacy' => true]);

        return to_route('dashboard')->with('status', "You're all set. Welcome aboard!");
    }
}