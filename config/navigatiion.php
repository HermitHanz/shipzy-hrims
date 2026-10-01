<?php
// 'route'  => route name (falls back to '#' if the route doesn't exist yet)
// 'active' => optional pattern for highlighting, e.g. 'admin.employees.*'
// 'can'    => optional permission; hidden if the user lacks it (plugs into your roles/permissions later)
return [
    'admin' => [
        [
            'heading' => null,
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'admin.dashboard'],
            ],
        ],
        [
            'heading' => 'People',
            'items' => [
                ['label' => 'Employees',  'icon' => 'users',    'route' => 'admin.employees.index', 'active' => 'admin.employees.*', 'can' => 'employees.view'],
                ['label' => 'Attendance', 'icon' => 'clock',    'route' => 'admin.attendance.index', 'active' => 'admin.attendance.*'],
                ['label' => 'Leaves',     'icon' => 'calendar', 'route' => 'admin.leaves.index', 'active' => 'admin.leaves.*'],
            ],
        ],
        [
            'heading' => 'System',
            'items' => [
                ['label' => 'Roles & Permissions', 'icon' => 'shield', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'can' => 'roles.manage'],
            ],
        ],
    ],

    'employee' => [
        [
            'heading' => null,
            'items' => [
                ['label' => 'Dashboard',  'icon' => 'home',     'route' => 'employee.dashboard'],
                ['label' => 'My Profile', 'icon' => 'user',     'route' => 'employee.profile'],
                ['label' => 'Attendance', 'icon' => 'clock',    'route' => 'employee.attendance'],
                ['label' => 'Leaves',     'icon' => 'calendar', 'route' => 'employee.leaves'],
                ['label' => 'Documents',  'icon' => 'document', 'route' => 'employee.documents'],
            ],
        ],
    ],
];