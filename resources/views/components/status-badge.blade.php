@props(['type' => 'employee', 'status'])
@php
    $map = [
        'employee' => ['active' => 'green', 'on_leave' => 'yellow', 'separated' => 'gray'],
        'bank'     => ['pending' => 'yellow', 'verified' => 'green', 'rejected' => 'red', 'superseded' => 'gray'],
        'account'  => ['active' => 'green', 'inactive' => 'gray', 'suspended' => 'red'],
    ];
@endphp
<x-ui.badge :variant="$map[$type][$status] ?? 'gray'">{{ \Illuminate\Support\Str::headline((string) $status) }}</x-ui.badge>