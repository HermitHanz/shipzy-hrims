<?php

/*
|--------------------------------------------------------------------------
| System settings registry
|--------------------------------------------------------------------------
| The Settings page is built from this file, so adding a setting means adding an entry here:
| no migration, controller or view change. Read a value anywhere with
|
|     App\Support\Settings\Settings::value('group.name', $fallback)
|
| Field keys: label, type (text | email | number | textarea | boolean | select), default, rules
| (Laravel validation rules), help, options (for select: value => label), sensitive (true
| redacts the value in the audit log).
|
| Real secrets (API keys, passwords) belong in .env, never here.
*/

return [

    'groups' => [

        'company' => [
            'label' => 'Company',
            'description' => 'Shown on the login page and in messages to employees.',
            'fields' => [
                'name' => [
                    'label' => 'Company name',
                    'type' => 'text',
                    'default' => env('APP_NAME', 'HRIMS'),
                    'rules' => ['required', 'string', 'max:100'],
                ],
                'hr_contact_email' => [
                    'label' => 'HR contact email',
                    'type' => 'email',
                    'default' => null,
                    'rules' => ['nullable', 'email', 'max:150'],
                    'help' => 'Shown on the login page for people who need help signing in.',
                ],
            ],
        ],

        'security' => [
            'label' => 'Security',
            'description' => 'Sign-in and password rules.',
            'fields' => [
                'max_login_attempts' => [
                    'label' => 'Failed sign-ins before lockout',
                    'type' => 'number',
                    'default' => 5,
                    'rules' => ['required', 'integer', 'min:3', 'max:20'],
                    'help' => 'Counted per email and IP address. The lockout lasts about a minute.',
                ],
                'password_min_length' => [
                    'label' => 'Minimum password length',
                    'type' => 'number',
                    'default' => 12,
                    'rules' => ['required', 'integer', 'min:8', 'max:64'],
                    'help' => 'Applies to new passwords. Passwords must also mix upper and lower case letters and include a number.',
                ],
            ],
        ],

        'onboarding' => [
            'label' => 'Onboarding',
            'description' => 'What new hires see when they first sign in.',
            'fields' => [
                'privacy_notice' => [
                    'label' => 'Data privacy notice',
                    'type' => 'textarea',
                    'default' => 'This is a placeholder. Replace it with your company data privacy notice in Settings.',
                    'rules' => ['required', 'string', 'max:10000'],
                    'help' => 'Shown during onboarding with an acknowledgement checkbox. Have your Data Protection Officer review the wording.',
                ],
            ],
        ],

        'audit' => [
            'label' => 'Audit log',
            'description' => 'How long audit entries are kept.',
            'fields' => [
                'retention_days' => [
                    'label' => 'Keep audit entries for (days)',
                    'type' => 'number',
                    'default' => 0,
                    'rules' => ['required', 'integer', 'min:0', 'max:3650'],
                    'help' => '0 keeps entries forever. Older entries are removed by the audit:prune command.',
                ],
            ],
        ],
    ],
];