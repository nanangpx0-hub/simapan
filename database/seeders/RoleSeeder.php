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

        foreach (array_keys($roles) as $slug) {
            $role = Role::firstOrCreate(
                ['name' => $slug, 'guard_name' => 'web']
            );

            if ($slug === 'administrator') {
                $role->syncPermissions(Permission::where('guard_name', 'web')->get());
            } else {
                $role->syncPermissions($basic);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
