<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

test('delapan role sistem tersedia dengan guard web', function (): void {
    $slugs = array_keys(config('simapan_roles.roles', []));

    expect($slugs)->toHaveCount(8);

    foreach ($slugs as $slug) {
        $role = Role::where('name', $slug)->where('guard_name', 'web')->first();
        expect($role)->not->toBeNull();
    }
});

test('dua puluh satu permission kanonik tersedia dengan guard web', function (): void {
    $names = config('simapan_roles.permissions', []);

    expect($names)->toHaveCount(21);

    foreach ($names as $name) {
        $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();
        expect($permission)->not->toBeNull();
    }
});

test('administrator memiliki seluruh 21 permission', function (): void {
    $admin = Role::where('name', 'administrator')->firstOrFail();

    expect($admin->permissions)->toHaveCount(21);

    foreach (config('simapan_roles.permissions', []) as $name) {
        expect($admin->hasPermissionTo($name))->toBeTrue();
    }
});

test('setiap role non-admin hanya memiliki dashboard dan profile', function (): void {
    $expected = ['dashboard.view', 'profile.manage'];

    foreach (['field_officer', 'field_supervisor', 'processing_officer', 'processing_supervisor', 'social_operator', 'ipds_operator', 'viewer'] as $slug) {
        $role = Role::where('name', $slug)->firstOrFail();

        expect($role->permissions->pluck('name')->sort()->values()->all())->toBe($expected);
    }
});

test('role non-admin tidak memiliki permission admin audit maupun master manage', function (): void {
    $denied = ['admin.user.manage', 'admin.role.manage', 'audit.view', 'master.work_unit.manage', 'master.survey_type.manage', 'master.survey_period.manage', 'master.region.manage', 'master.officer.manage', 'allocation.view', 'allocation.manage', 'allocation.assign', 'dsrt.view', 'dsrt.manage', 'dsrt.verify'];

    foreach (['field_officer', 'social_operator', 'ipds_operator', 'viewer'] as $slug) {
        $role = Role::where('name', $slug)->firstOrFail();

        foreach ($denied as $name) {
            expect($role->hasPermissionTo($name))->toBeFalse();
        }
    }
});
