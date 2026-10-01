<?php

/*
| Modules that need the user's password before they can be opened.
| Protect a module's routes with:  ->middleware('module.password:<key>')
|
|   label        shown on the unlock page
|   description  one line explaining why it is protected
|   permissions  any-of; the unlock page itself is only reachable with one of these
|   home         route name to land on if there is no remembered URL
|   ttl          minutes unlocked (defaults to default_ttl)
|   sliding      true = timeout resets on activity (idle timeout), false = fixed from unlock
*/

return [

    'default_ttl' => 15,

    'modules' => [

        'audit-log' => [
            'label'       => 'Audit log',
            'description' => 'The audit log contains sensitive activity records. Confirm your password to continue.',
            'permissions' => ['system.audit-log.view'],
            'home'        => 'admin.audit-logs.index',
        ],

    ],
];