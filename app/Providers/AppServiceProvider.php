<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Policies\{RolePolicy, UserPolicy};
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Support\Settings\Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability, $arguments = []) {
            // Plain permission checks bypass for Super Admin; model-based checks go through policies.
            if (! empty($arguments)) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        View::composer(
            ['dashboard.index', 'me.profile'],
            \App\View\Composers\ProfileNudgeComposer::class
        );
    }
}
