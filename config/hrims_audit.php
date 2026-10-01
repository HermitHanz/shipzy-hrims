<?php

/*
|--------------------------------------------------------------------------
| Audit log catalog
|--------------------------------------------------------------------------
| Everything here is OPTIONAL. Any module can write an entry with
|
|     app(AuditLogger::class)->log('leave.request_approved', $leaveRequest, $old, $new, $actor);
|
| and the viewer will show it straight away: the first segment of the action ("leave")
| becomes its category and the rest is turned into a readable title. Add an entry below
| only to give an event a nicer label or a higher severity, or to teach the viewer how to
| label and link a new kind of target record.
|
| Severity: info (routine), notice (access, pay or status changes), warning (security-relevant).
*/

return [

    'categories' => [
        'auth' => 'Authentication',
        'user' => 'Users',
        'role' => 'Roles',
        'employee' => 'Employees',
        'settings' => 'Settings',
        'audit' => 'Audit log',
    ],

    'events' => [
        'auth.login' => ['label' => 'Signed in', 'severity' => 'info'],
        'auth.login_failed' => ['label' => 'Failed sign-in attempt', 'severity' => 'warning'],
        'auth.lockout' => ['label' => 'Sign-in locked out', 'severity' => 'warning'],
        'auth.logout' => ['label' => 'Signed out', 'severity' => 'info'],
        'auth.password_changed' => ['label' => 'Password changed', 'severity' => 'notice'],

        'user.created' => ['label' => 'User account created', 'severity' => 'notice'],
        'user.super_admin_created' => ['label' => 'Super Admin created', 'severity' => 'warning'],
        'user.roles_changed' => ['label' => 'User roles changed', 'severity' => 'notice'],
        'user.permissions_changed' => ['label' => 'User permissions changed', 'severity' => 'notice'],
        'user.status_changed' => ['label' => 'User status changed', 'severity' => 'notice'],
        'user.password_reset' => ['label' => 'Password reset by an administrator', 'severity' => 'notice'],

        'role.created' => ['label' => 'Role created', 'severity' => 'notice'],
        'role.updated' => ['label' => 'Role updated', 'severity' => 'notice'],
        'role.deleted' => ['label' => 'Role deleted', 'severity' => 'warning'],

        'employee.created' => ['label' => 'Employee created', 'severity' => 'info'],
        'employee.updated' => ['label' => 'Employee updated', 'severity' => 'info'],
        'employee.status_changed' => ['label' => 'Employee status changed', 'severity' => 'notice'],
        'employee.personal_updated' => ['label' => 'Personal details updated', 'severity' => 'info'],
        'employee.onboarding_completed' => ['label' => 'Onboarding completed', 'severity' => 'info'],
        'employee.emergency_contacts_updated' => ['label' => 'Emergency contacts updated', 'severity' => 'info'],
        'employee.government_ids_updated' => ['label' => 'Government IDs updated', 'severity' => 'notice'],
        'employee.bank_account_submitted' => ['label' => 'Bank account submitted', 'severity' => 'notice'],
        'employee.bank_account_reviewed' => ['label' => 'Bank account reviewed', 'severity' => 'notice'],
        'employee.sensitive_revealed' => ['label' => 'Sensitive data revealed', 'severity' => 'warning'],

        'settings.updated' => ['label' => 'Settings changed', 'severity' => 'notice'],

        'audit.exported' => ['label' => 'Audit log exported', 'severity' => 'warning'],
        'audit.pruned' => ['label' => 'Old audit entries removed', 'severity' => 'notice'],
    ],

    /*
    | How to label (and optionally link) a target record. The key is the target_type the logger
    | stores: the snake_case class name of the model passed to log(). 'route' is a named route
    | that takes the record id; leave it null if there is no page for it.
    */
    'targets' => [
        'user' => ['model' => \App\Models\User::class, 'label' => 'name', 'route' => null],
        'role' => ['model' => \Spatie\Permission\Models\Role::class, 'label' => 'label', 'fallback' => 'name', 'route' => null],
        'employee' => ['model' => \App\Models\Employee::class, 'label' => 'full_name', 'route' => 'employees.show'],
    ],
];