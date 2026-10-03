<?php

// 'permissions' => shown if the user holds ANY of them (omit = every signed-in user).
// Items are hidden automatically until their named route exists.

return [
    ['heading' => null, 'items' => [
        ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard'],
    ]],

    ['heading' => 'My space', 'items' => [
        ['label' => 'My profile', 'icon' => 'user', 'route' => 'profile.show', 'active' => 'profile.*', 'permissions' => ['employee.record.view-own']],
        ['label' => 'My attendance','icon' => 'clock',    'route' => 'me.attendance', 'active' => 'me.attendance*', 'permissions' => ['attendance.record.view-own']],
        ['label' => 'My leave',     'icon' => 'calendar', 'route' => 'me.leave',      'active' => 'me.leave*',      'permissions' => ['leave.request.view-own']],
        ['label' => 'My payslips',  'icon' => 'document', 'route' => 'me.payslips',   'active' => 'me.payslips*',   'permissions' => ['payroll.payslip.view-own']],
        ['label' => 'My documents', 'icon' => 'document', 'route' => 'me.documents',  'active' => 'me.documents*',  'permissions' => ['documents.file.view-own']],
    ]],

    ['heading' => 'People & time', 'items' => [
        ['label' => 'Employees', 'icon' => 'users', 'route' => 'employees.index',
        'active' => ['employees.index', 'employees.create', 'employees.show', 'employees.edit'],
        'permissions' => ['employee.record.view-team', 'employee.record.view-department', 'employee.record.view-all']],
        ['label' => 'Bank verification', 'icon' => 'banknotes', 'route' => 'employees.bank-verification.index',
        'active' => 'employees.bank-verification.*', 'permissions' => ['employee.bank.verify']],
        ['label' => 'Attendance', 'icon' => 'clock',    'route' => 'attendance.index', 'active' => 'attendance.*', 'permissions' => ['attendance.record.view-team', 'attendance.record.view-department', 'attendance.record.view-all']],
        ['label' => 'Leave',      'icon' => 'calendar', 'route' => 'leave.index',      'active' => 'leave.*',      'permissions' => ['leave.request.view-team', 'leave.request.view-department', 'leave.request.view-all', 'leave.request.approve-team', 'leave.request.approve-all']],
    ]],

    ['heading' => 'Organization', 'items' => [
        ['label' => 'Branches',    'icon' => 'building-office', 'route' => 'organization.branches.index',    'active' => 'organization.branches.*',    'permissions' => ['organization.branch.view']],
        ['label' => 'Departments', 'icon' => 'rectangle-group', 'route' => 'organization.departments.index', 'active' => 'organization.departments.*', 'permissions' => ['organization.department.view']],
    ]],

    ['heading' => 'Operations', 'items' => [
        ['label' => 'Payroll',   'icon' => 'document',  'route' => 'payroll.index',   'active' => 'payroll.*',   'permissions' => ['payroll.run.view', 'payroll.payslip.view-all']],
        ['label' => 'Documents', 'icon' => 'document',  'route' => 'documents.index', 'active' => 'documents.*', 'permissions' => ['documents.file.view-all']],
        ['label' => 'Reports',   'icon' => 'chart-bar', 'route' => 'reports.index',   'active' => 'reports.*',   'permissions' => ['reports.report.view']],
    ]],

    ['heading' => 'Administration', 'items' => [
        ['label' => 'Users',               'icon' => 'users',    'route' => 'admin.users.index',      'active' => 'admin.users.*',      'permissions' => ['system.user.view']],
        ['label' => 'Roles & permissions', 'icon' => 'shield',   'route' => 'admin.roles.index',      'active' => 'admin.roles.*',      'permissions' => ['system.role.view']],
        ['label' => 'Audit logs',          'icon' => 'document', 'route' => 'admin.audit-logs.index', 'active' => 'admin.audit-logs.*', 'permissions' => ['system.audit-log.view']],
        ['label' => 'Settings',            'icon' => 'settings', 'route' => 'admin.settings.edit',   'active' => 'admin.settings.*',   'permissions' => ['system.settings.view']],
    ]],
];