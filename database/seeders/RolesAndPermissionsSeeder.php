<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $keys = $this->syncPermissions();
        $this->syncRoles($keys);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Create every registry permission; returns the full list of keys. */
    private function syncPermissions(): array
    {
        $keys = [];

        foreach (config('hrims_permissions.modules') as $module => $resources) {
            foreach ($resources as $resource => $actions) {
                foreach ($actions as $action) {
                    $key = "{$module}.{$resource}.{$action}";
                    Permission::firstOrCreate(['name' => $key, 'guard_name' => self::GUARD]);
                    $keys[] = $key;
                }
            }
        }

        // Permissions are code-defined: remove any that were dropped from the registry.
        Permission::where('guard_name', self::GUARD)->whereNotIn('name', $keys)->delete();

        return $keys;
    }

    private function syncRoles(array $keys): void
    {
        foreach (config('hrims_permissions.roles') as $name => $def) {
            $role = Role::updateOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                [
                    'label'       => $def['label'],
                    'description' => $def['description'],
                    'level'       => $def['level'],
                    'is_system'   => true,
                ]
            );

            // Super Admin always mirrors the full registry. Other system roles are only
            // seeded on first creation, so re-running the seeder never wipes changes
            // made through the role management UI.
            if ($name === 'super-admin' || $role->wasRecentlyCreated) {
                $granted = array_filter($keys, fn ($key) =>
                    $this->matches($key, $def['grant']) && ! $this->matches($key, $def['except'] ?? [])
                );

                $role->syncPermissions(array_values($granted));
            }
        }
    }

    private function matches(string $key, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $key)) {
                return true;
            }
        }

        return false;
    }
}