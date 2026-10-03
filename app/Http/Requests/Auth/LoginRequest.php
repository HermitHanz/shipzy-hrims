<?php

namespace App\Http\Requests\Auth;

use App\Support\Audit\AuditLogger;
use App\Support\Settings\Settings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt login. Only 'active' accounts can authenticate; inactive and unknown
     * accounts get the same generic error so status isn't leaked.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->string('email')->toString();

        $credentials = [
            'email' => $email,
            'password' => $this->string('password')->toString(),
            'status' => 'active',
            // A separated employee can never sign in, even if their login was left active.
            fn ($query) => $query->whereDoesntHave('employee', fn ($employee) => $employee->where('status', 'separated')),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            app(AuditLogger::class)->log('auth.login_failed', null, null, ['email' => $email]);

            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        app(AuditLogger::class)->log('auth.login', $user);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), (int) Settings::value('security.max_login_attempts', 5))) {
            return;
        }

        app(AuditLogger::class)->log('auth.lockout', null, null, ['email' => $this->string('email')->toString()]);

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => (int) ceil($seconds / 60)]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->toString()).'|'.$this->ip());
    }
}
