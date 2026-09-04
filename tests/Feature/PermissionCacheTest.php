<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

test('hasil permission tetap benar setelah seeding ulang', function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->create();
    $user->assignRole('administrator');

    expect($user->can('admin.user.manage'))->toBeTrue();
    expect($user->can('master.officer.manage'))->toBeTrue();
});

test('permission baru efektif setelah cache reset', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    expect($user->can('audit.view'))->toBeFalse();

    $user->givePermissionTo('audit.view');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($user->refresh()->can('audit.view'))->toBeTrue();
});

test('registrar cache reset tidak merusak resolusi permission', function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Permission::where('guard_name', 'web')->count())->toBe(25);
});
