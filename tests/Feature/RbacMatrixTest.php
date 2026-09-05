<?php

declare(strict_types=1);

use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

test('sembilan role sistem tersedia dengan guard web', function (): void {
    $slugs = array_keys(config('simapan_roles.roles', []));

    expect($slugs)->toHaveCount(9);

    foreach ($slugs as $slug) {
        $role = Role::where('name', $slug)->where('guard_name', 'web')->first();
        expect($role)->not->toBeNull();
    }
});

test('dua puluh lima permission kanonik tersedia dengan guard web', function (): void {
    $names = config('simapan_roles.permissions', []);

    expect($names)->toHaveCount(25);

    foreach ($names as $name) {
        $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();
        expect($permission)->not->toBeNull();
    }
});

test('administrator memiliki seluruh 25 permission', function (): void {
    $admin = Role::where('name', 'administrator')->firstOrFail();

    expect($admin->permissions)->toHaveCount(25);

    foreach (config('simapan_roles.permissions', []) as $name) {
        expect($admin->hasPermissionTo($name))->toBeTrue();
    }
});

test('super_admin memiliki seluruh 25 permission', function (): void {
    $super = Role::where('name', 'super_admin')->firstOrFail();

    expect($super->permissions)->toHaveCount(25);

    foreach (config('simapan_roles.permissions', []) as $name) {
        expect($super->hasPermissionTo($name))->toBeTrue();
    }
});

test('super_admin bypass gate sehingga akses penuh', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($user->can('super-admin'))->toBeTrue();
    expect($user->can('admin.user.manage'))->toBeTrue();
    expect($user->can('dsrt.verify'))->toBeTrue();
    expect($user->can('document.receive'))->toBeTrue();
    expect($user->hasFullDataScope())->toBeTrue();
});

test('role non-super tidak dapat gate super-admin', function (): void {
    foreach (['administrator', 'viewer', 'field_officer', 'social_operator'] as $slug) {
        $user = User::factory()->create();
        $user->assignRole($slug);

        expect($user->can('super-admin'))->toBeFalse();
    }
});

test('matriks role-permission kanonik Fase 2 sesuai config', function (): void {
    /** @var array<string, mixed> $matrix */
    $matrix = config('simapan_roles.role_permissions', []);

    /** @var list<string> $basic */
    $basic = config('simapan_roles.basic_permissions', []);

    foreach ($matrix as $slug => $granted) {
        if ($slug === 'administrator' || $slug === 'super_admin') {
            continue;
        }

        $role = Role::where('name', $slug)->firstOrFail();
        $expected = array_values(array_unique(array_merge($basic, $granted)));

        expect($role->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($expected)->sort()->values()->all());
    }
});

test('viewer hanya memiliki permission view plus basic', function (): void {
    $role = Role::where('name', 'viewer')->firstOrFail();

    $expected = [
        'dashboard.view',
        'profile.manage',
        'audit.view',
        'master.work_unit.view',
        'master.survey_type.view',
        'master.survey_period.view',
        'master.region.view',
        'master.officer.view',
        'allocation.view',
        'dsrt.view',
        'document.view',
    ];

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect($expected)->sort()->values()->all());
});

test('social_operator memiliki matriks parsial sosial', function (): void {
    $role = Role::where('name', 'social_operator')->firstOrFail();

    foreach (['master.work_unit.view', 'master.survey_period.view', 'master.survey_type.view', 'master.region.view', 'master.officer.view', 'allocation.view', 'allocation.manage', 'allocation.assign', 'dsrt.view', 'document.view', 'document.manage'] as $name) {
        expect($role->hasPermissionTo($name))->toBeTrue();
    }

    foreach (['dsrt.manage', 'dsrt.verify', 'document.receive', 'document.assign', 'admin.user.manage', 'audit.view'] as $name) {
        expect($role->hasPermissionTo($name))->toBeFalse();
    }
});

test('ipds_operator memiliki matriks parsial ipds', function (): void {
    $role = Role::where('name', 'ipds_operator')->firstOrFail();

    foreach (['master.work_unit.view', 'master.survey_period.view', 'master.region.view', 'master.officer.view', 'master.officer.manage', 'allocation.view', 'document.view', 'document.manage', 'document.receive', 'document.assign'] as $name) {
        expect($role->hasPermissionTo($name))->toBeTrue();
    }

    foreach (['allocation.manage', 'allocation.assign', 'dsrt.view', 'dsrt.manage', 'dsrt.verify', 'admin.user.manage'] as $name) {
        expect($role->hasPermissionTo($name))->toBeFalse();
    }
});

test('field_supervisor memiliki matriks PML', function (): void {
    $role = Role::where('name', 'field_supervisor')->firstOrFail();

    foreach (['allocation.view', 'allocation.assign', 'dsrt.view', 'dsrt.verify', 'document.view', 'document.manage'] as $name) {
        expect($role->hasPermissionTo($name))->toBeTrue();
    }

    foreach (['allocation.manage', 'dsrt.manage', 'document.receive', 'document.assign', 'admin.user.manage'] as $name) {
        expect($role->hasPermissionTo($name))->toBeFalse();
    }
});

test('field_officer memiliki matriks PPL', function (): void {
    $role = Role::where('name', 'field_officer')->firstOrFail();

    foreach (['allocation.view', 'dsrt.view', 'dsrt.manage', 'document.view'] as $name) {
        expect($role->hasPermissionTo($name))->toBeTrue();
    }

    foreach (['allocation.manage', 'allocation.assign', 'dsrt.verify', 'document.manage', 'admin.user.manage'] as $name) {
        expect($role->hasPermissionTo($name))->toBeFalse();
    }
});

test('processing_supervisor dan processing_officer memiliki matriks pengolahan', function (): void {
    $supervisor = Role::where('name', 'processing_supervisor')->firstOrFail();
    $officer = Role::where('name', 'processing_officer')->firstOrFail();

    foreach (['master.work_unit.view', 'master.officer.view', 'document.view', 'document.manage', 'document.receive', 'document.assign'] as $name) {
        expect($supervisor->hasPermissionTo($name))->toBeTrue();
    }

    expect($officer->hasPermissionTo('document.view'))->toBeTrue();

    foreach (['document.manage', 'document.receive', 'document.assign', 'allocation.view', 'dsrt.view'] as $name) {
        expect($officer->hasPermissionTo($name))->toBeFalse();
    }
});

test('role non-admin tidak memiliki permission admin maupun audit', function (): void {
    $denied = ['admin.user.manage', 'admin.role.manage', 'audit.view'];

    foreach (['field_officer', 'field_supervisor', 'processing_officer', 'processing_supervisor', 'social_operator', 'ipds_operator'] as $slug) {
        $role = Role::where('name', $slug)->firstOrFail();

        foreach ($denied as $name) {
            expect($role->hasPermissionTo($name))->toBeFalse();
        }
    }

    foreach (['field_officer', 'field_supervisor', 'processing_officer', 'processing_supervisor', 'social_operator', 'ipds_operator', 'viewer'] as $slug) {
        $role = Role::where('name', $slug)->firstOrFail();

        foreach (['admin.user.manage', 'admin.role.manage'] as $name) {
            expect($role->hasPermissionTo($name))->toBeFalse();
        }
    }
});

test('pemetaan role spatie dan assignment saling setara', function (): void {
    foreach (User::ROLE_ASSIGNMENT_MAP as $spatieRole => $assignmentRole) {
        expect(User::assignmentRoleFor($spatieRole))->toBe($assignmentRole);
        expect(User::spatieRoleFor($assignmentRole))->toBe($spatieRole);
    }

    expect(User::spatieRoleFor('UNKNOWN_ROLE'))->toBeNull();
    expect(User::assignmentRoleFor('unknown_role'))->toBeNull();
});
