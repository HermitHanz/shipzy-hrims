<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // TODO: replace each block with real queries (run them through your scope resolver
        // so an HR Admin only sees the branches/departments they're allowed to).

        $stats = [
            ['label' => 'Total employees',   'value' => 128, 'icon' => 'users',    'note' => '+4 this month',   'href' => 'admin.employees.index', 'can' => ['employee.record.view-all']],
            ['label' => 'Present today',     'value' => 112, 'icon' => 'clock',    'note' => '87.5% attendance', 'href' => 'admin.attendance.index', 'can' => ['attendance.record.view-all']],
            ['label' => 'On leave',          'value' => 9,   'icon' => 'calendar', 'note' => '3 starting tomorrow', 'href' => 'admin.leaves.index', 'can' => ['leave.request.view-all']],
            ['label' => 'Pending requests',  'value' => 7,   'icon' => 'document', 'note' => 'Needs your review', 'href' => 'admin.leaves.index', 'can' => ['leave.request.approve-team', 'leave.request.approve-all']],
        ];

        // Attendance rate (%) for the last 7 days
        $attendance = [
            ['day' => 'Mon', 'rate' => 92],
            ['day' => 'Tue', 'rate' => 88],
            ['day' => 'Wed', 'rate' => 95],
            ['day' => 'Thu', 'rate' => 90],
            ['day' => 'Fri', 'rate' => 84],
            ['day' => 'Sat', 'rate' => 40],
            ['day' => 'Sun', 'rate' => 0],
        ];

        $pending = [
            ['name' => 'Maria Santos',    'type' => 'Vacation leave', 'date' => 'Oct 6–8'],
            ['name' => 'Juan Dela Cruz',  'type' => 'Sick leave',     'date' => 'Oct 2'],
            ['name' => 'Ana Reyes',       'type' => 'Overtime',       'date' => 'Sep 27'],
        ];

        $recentEmployees = [
            ['name' => 'Carlo Mendoza', 'department' => 'Engineering', 'branch' => 'Main Office', 'status' => 'Active',    'joined' => 'Sep 22'],
            ['name' => 'Liza Ramos',    'department' => 'Finance',     'branch' => 'Main Office', 'status' => 'Probation', 'joined' => 'Sep 15'],
            ['name' => 'Paolo Cruz',    'department' => 'Operations',  'branch' => 'Branch 2',    'status' => 'Active',    'joined' => 'Sep 8'],
        ];

        $quickActions = [
            ['label' => 'Add employee',   'route' => 'admin.employees.create', 'can' => 'system.user.create'],
            ['label' => 'Manage roles',   'route' => 'admin.roles.index',      'can' => 'system.role.view'],
        ];

        return view('dashboard.index', compact('stats', 'attendance', 'pending', 'recentEmployees', 'quickActions'));
    }
}