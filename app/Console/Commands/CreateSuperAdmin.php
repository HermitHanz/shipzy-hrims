<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    protected $signature = 'hrims:create-super-admin {--name=} {--email=}';

    protected $description = 'Create a Super Admin user (run RolesAndPermissionsSeeder first)';

    public function handle(): int
    {
        if (! Role::where('name', 'super-admin')->where('guard_name', 'web')->exists()) {
            $this->error('The super-admin role does not exist. Run: php artisan db:seed --class=RolesAndPermissionsSeeder');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->secret('Password (minimum 12 characters)');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12', 'confirmed'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User();
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'active',
            'email_verified_at' => now(),
        ])->save();

        $user->assignRole('super-admin');

        DB::table('audit_logs')->insert([
            'actor_id' => null,
            'action' => 'user.super_admin_created',
            'target_type' => 'user',
            'target_id' => $user->id,
            'new_values' => json_encode(['email' => $email]),
            'created_at' => now(),
        ]);

        $this->info("Super Admin created: {$email}");

        return self::SUCCESS;
    }
}