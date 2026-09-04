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

test('semua role memakai guard web', function (): void {
    expect(Role::where('guard_name', '!=', 'web')->count())->toBe(0);
    expect(Role::where('guard_name', 'web')->count())->toBe(8);
});

test('semua permission memakai guard web', function (): void {
    expect(Permission::where('guard_name', '!=', 'web')->count())->toBe(0);
    expect(Permission::where('guard_name', 'web')->count())->toBe(25);
});

test('konfigurasi teams bernilai false', function (): void {
    expect(config('permission.teams'))->toBeFalse();
});
