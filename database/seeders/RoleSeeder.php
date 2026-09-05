<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var array<string, string> $roles */
        $roles = config('simapan_roles.roles', []);

        /** @var list<string> $basic */
        $basic = config('simapan_roles.basic_permissions', []);

        /** @var array<string, mixed> $matrix */
        $matrix = config('simapan_roles.role_permissions', []);

        $allPermissions = Permission::where('guard_name', 'web')->get();

        foreach (array_keys($roles) as $slug) {
            $role = Role::firstOrCreate(
                ['name' => $slug, 'guard_name' => 'web']
            );

            if ($slug === 'super_admin' || $slug === 'administrator') {
                $role->syncPermissions($allPermissions);

                continue;
            }

            /** @var list<string> $granted */
            $granted = array_values(array_unique(array_merge(
                $basic,
                is_array($matrix[$slug] ?? null) ? $matrix[$slug] : []
            )));

            $role->syncPermissions($granted);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
