<?php

/*
|--------------------------------------------------------------------------
| HRIMS Permission Registry
|--------------------------------------------------------------------------
| Single source of truth for permissions. Keys are built as:
|     module.resource.action     e.g. leave.request.approve-team
|
| Scope convention (enforced in policies / query scopes, not here):
|     view-own | view-team | view-department | view-all
|
| To add a module: add it under 'modules', then run:
|     php artisan db:seed --class=RolesAndPermissionsSeeder
*/

return [

    'modules' => [

        'employee' => [
            'record' => ['view-own', 'view-team', 'view-department', 'view-all', 'create', 'edit', 'delete', 'export', 'reassign'],
            'salary' => ['view-own', 'view-all', 'edit'],

            // Protected data. *-own lets a person see/edit their own; view-all/edit is for HR.
            // 'reveal' shows full numbers and is audited every time.
            'personal'      => ['view-own', 'view-all', 'edit-own', 'edit'],
            'emergency'     => ['view-own', 'view-all', 'edit-own', 'edit'],
            'government-id' => ['view-own', 'view-all', 'edit-own', 'edit', 'reveal'],
            'bank'          => ['view-own', 'view-all', 'edit-own', 'edit', 'verify', 'reveal'],
        ],

        'attendance' => [
            'record'     => ['view-own', 'view-team', 'view-department', 'view-all', 'edit', 'export'],
            'correction' => ['create', 'approve-team', 'approve-all'],
        ],

        'leave' => [
            'request' => ['view-own', 'view-team', 'view-department', 'view-all', 'create', 'cancel', 'approve-team', 'approve-all'],
            'balance' => ['view-own', 'view-all', 'edit'],
        ],

        'payroll' => [
            'payslip' => ['view-own', 'view-all', 'generate', 'export'],
            'run'     => ['view', 'create', 'approve'],
        ],

        'documents' => [
            'file' => ['view-own', 'view-all', 'create', 'delete'],
        ],

        'reports' => [
            'report' => ['view', 'export'],
        ],

        'system' => [
            'user'      => ['view', 'create', 'edit', 'delete', 'deactivate'],
            'role'      => ['view', 'create', 'edit', 'delete', 'assign'],
            'settings'  => ['view', 'edit'],
            'audit-log' => ['view', 'export'],
        ],
    ],

    /*
    | System roles. 'grant' and 'except' accept exact keys or * wildcards.
    | 'level' drives anti-escalation: users can only manage users/roles with a lower level.
    */
    'roles' => [

        'super-admin' => [
            'label'       => 'Super Admin',
            'description' => 'Full access to everything. Highest authority.',
            'level'       => 100,
            'grant'       => ['*'],
            'except'      => [],
        ],

        'hr-admin' => [
            'label'       => 'HR Admin',
            'description' => 'Manages HR modules, employee accounts, and access.',
            'level'       => 50,
            'grant'       => ['*'],
            'except'      => ['system.settings.*', 'system.audit-log.*', 'employee.government-id.reveal', 'employee.bank.reveal'],
        ],

        'employee' => [
            'label'       => 'Employee',
            'description' => 'Self-service access. Extra access comes from custom roles or direct permissions.',
            'level'       => 10,
            'grant'       => [
                'employee.record.view-own',
                'employee.salary.view-own',
                'attendance.record.view-own',
                'attendance.correction.create',
                'leave.request.view-own',
                'leave.request.create',
                'leave.request.cancel',
                'leave.balance.view-own',
                'payroll.payslip.view-own',
                'documents.file.view-own',
                'employee.personal.view-own',
                'employee.personal.edit-own',
                'employee.emergency.view-own',
                'employee.emergency.edit-own',
                'employee.government-id.view-own',
                'employee.government-id.edit-own',
                'employee.bank.view-own',
                'employee.bank.edit-own',
            ],
            'except'      => [],
        ],
    ],
];