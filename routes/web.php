<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Employee\BankVerificationController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\EmployeeLoginController;
use App\Http\Controllers\Employee\EmployeeSectionController;
use App\Http\Controllers\Employee\EmployeeStatusController;
use App\Http\Controllers\Employee\OnboardingController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\RevealController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ModuleUnlockController;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', DashboardController::class . '@index')->name('dashboard');

    /*
    * Admin Group Routes
    */
    Route::prefix('admin')->name('admin.')->group(function () {

        // Users (static routes BEFORE {user})
        Route::controller(UserController::class)->group(function () {
            Route::get('users', 'index')->middleware('permission:system.user.view')->name('users.index');
            Route::get('users/create', 'create')->middleware('permission:system.user.create')->name('users.create');
            Route::post('users', 'store')->middleware('permission:system.user.create')->name('users.store');
            Route::get('users/credentials', 'credentials')->middleware('permission:system.user.create|system.user.edit')->name('users.credentials');

            Route::prefix('users/{user}')->where(['user' => '[0-9]+'])->group(function () {
                Route::get('access', 'access')->middleware('permission:system.user.view')->name('users.access');
                Route::put('roles', 'updateRoles')->middleware('permission:system.role.assign')->name('users.roles.update');
                Route::put('permissions', 'updatePermissions')->middleware('permission:system.role.assign')->name('users.permissions.update');
                Route::patch('status', 'updateStatus')->middleware('permission:system.user.deactivate')->name('users.status.update');
                Route::post('reset-password', 'resetPassword')->middleware('permission:system.user.edit')->name('users.password.reset');
            });
        });

        // Roles
        Route::controller(RoleController::class)->group(function () {
            Route::get('roles', 'index')->middleware('permission:system.role.view')->name('roles.index');
            Route::get('roles/create', 'create')->middleware('permission:system.role.create')->name('roles.create');
            Route::post('roles', 'store')->middleware('permission:system.role.create')->name('roles.store');
            Route::get('roles/{role}/edit', 'edit')->middleware('permission:system.role.view')->name('roles.edit');
            Route::put('roles/{role}', 'update')->middleware('permission:system.role.edit')->name('roles.update');
            Route::delete('roles/{role}', 'destroy')->middleware('permission:system.role.delete')->name('roles.destroy');
        });

        // Audit Logs
        Route::middleware(['permission:system.audit-log.view', 'module.password:audit-log'])->group(function () {
            Route::get('audit-logs', [AuditLogController::class, 'index'])
                ->middleware('permission:system.audit-log.view')->name('audit-logs.index');
            Route::get('audit-logs/export', [AuditLogController::class, 'export'])
                ->middleware(['permission:system.audit-log.view', 'throttle:10,1'])->name('audit-logs.export');
        });

        // Settings
        Route::get('settings', [SettingsController::class, 'edit'])
            ->middleware('permission:system.settings.view')->name('settings.edit');
        Route::put('settings/{group}', [SettingsController::class, 'update'])
            ->middleware('permission:system.settings.edit')->where('group', '[A-Za-z0-9_.-]+')->name('settings.update');
    }); // End of Admin Group Routes

    //Authenticated users can unlock modules (if they have the required permissions)
    Route::get('unlock/{module}', [ModuleUnlockController::class, 'show'])->name('module.unlock.show');
    Route::post('unlock/{module}', [ModuleUnlockController::class, 'store'])->middleware('throttle:20,1')->name('module.unlock.store');
    Route::delete('unlock/{module}', [ModuleUnlockController::class, 'destroy'])->name('module.unlock.destroy');
    /*
    *
    * Employee Group Routes
    *
    */

    // Onboarding + self-service (auth only; policies decide own-vs-all)
    Route::get('onboarding', [OnboardingController::class, 'edit'])->name('onboarding.edit');
    Route::put('onboarding', [OnboardingController::class, 'update'])->name('onboarding.update');

    Route::prefix('me/profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::put('personal', [EmployeeSectionController::class, 'personal'])->name('personal.update');
        Route::put('emergency-contacts', [EmployeeSectionController::class, 'emergency'])->name('emergency.update');
        Route::put('government-ids', [EmployeeSectionController::class, 'governmentIds'])->name('government-ids.update');
        Route::post('bank-account', [EmployeeSectionController::class, 'storeBankAccount'])->name('bank-account.store');
    });

    // Employee management (static routes BEFORE {employee})
    Route::prefix('employees')->name('employees.')->group(function () {
        $viewAny = 'permission:employee.record.view-team|employee.record.view-department|employee.record.view-all';

        Route::get('/', [EmployeeController::class, 'index'])->middleware($viewAny)->name('index');
        Route::get('create', [EmployeeController::class, 'create'])->middleware('permission:employee.record.create')->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->middleware('permission:employee.record.create')->name('store');
        Route::get('bank-verification', [BankVerificationController::class, 'index'])
            ->middleware('permission:employee.bank.verify')->name('bank-verification.index');

        Route::prefix('{employee}')->where(['employee' => '[0-9]+'])->group(function () use ($viewAny) {
            Route::get('/', [EmployeeController::class, 'show'])->middleware($viewAny)->name('show');
            Route::get('edit', [EmployeeController::class, 'edit'])->middleware('permission:employee.record.edit')->name('edit');
            Route::put('/', [EmployeeController::class, 'update'])->middleware('permission:employee.record.edit')->name('update');

            Route::put('status', [EmployeeStatusController::class, 'update'])->middleware('permission:employee.record.edit')->name('status.update');
            Route::post('login', [EmployeeLoginController::class, 'store'])->middleware('permission:system.user.create')->name('login.store');

            Route::put('personal', [EmployeeSectionController::class, 'personal'])->middleware('permission:employee.personal.edit')->name('personal.update');
            Route::put('emergency-contacts', [EmployeeSectionController::class, 'emergency'])->middleware('permission:employee.emergency.edit')->name('emergency.update');
            Route::put('government-ids', [EmployeeSectionController::class, 'governmentIds'])->middleware('permission:employee.government-id.edit')->name('government-ids.update');
            Route::post('bank-accounts', [EmployeeSectionController::class, 'storeBankAccount'])->middleware('permission:employee.bank.edit')->name('bank-accounts.store');

            Route::post('bank-accounts/{bankAccount}/review', [BankVerificationController::class, 'review'])
                ->middleware('permission:employee.bank.verify')->scopeBindings()->name('bank-accounts.review');

            // Own reveals use this too, so no permission: middleware; the policy decides
            Route::post('reveal', [RevealController::class, 'store'])->middleware('throttle:20,1')->name('reveal');
        });
    });
}); // End Employee Group Routes

// if (app()->environment('local')) {
//     Route::view('/dev/layout', 'dev.layout-test')->middleware('auth');
// }

require __DIR__.'/auth.php';
