<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DevelopmentAdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    putenv('SIMAPAN_ADMIN_NAME=Admin Dummy Uji');
    putenv('SIMAPAN_ADMIN_EMAIL=admin-uji@simapan.test');
    putenv('SIMAPAN_ADMIN_PASSWORD=dummy-uji-01');
    $_ENV['SIMAPAN_ADMIN_NAME'] = 'Admin Dummy Uji';
    $_ENV['SIMAPAN_ADMIN_EMAIL'] = 'admin-uji@simapan.test';
    $_ENV['SIMAPAN_ADMIN_PASSWORD'] = 'dummy-uji-01';
    $_SERVER['SIMAPAN_ADMIN_NAME'] = 'Admin Dummy Uji';
    $_SERVER['SIMAPAN_ADMIN_EMAIL'] = 'admin-uji@simapan.test';
    $_SERVER['SIMAPAN_ADMIN_PASSWORD'] = 'dummy-uji-01';
});

afterEach(function (): void {
    putenv('SIMAPAN_ADMIN_NAME');
    putenv('SIMAPAN_ADMIN_EMAIL');
    putenv('SIMAPAN_ADMIN_PASSWORD');
    unset($_ENV['SIMAPAN_ADMIN_NAME'], $_ENV['SIMAPAN_ADMIN_EMAIL'], $_ENV['SIMAPAN_ADMIN_PASSWORD']);
    unset($_SERVER['SIMAPAN_ADMIN_NAME'], $_SERVER['SIMAPAN_ADMIN_EMAIL'], $_SERVER['SIMAPAN_ADMIN_PASSWORD']);
});

test('seeder dapat dijalankan dua kali tanpa duplikat', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, DevelopmentAdminSeeder::class]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class, DevelopmentAdminSeeder::class]);

    expect(Role::where('guard_name', 'web')->count())->toBe(8);
    expect(Permission::where('guard_name', 'web')->count())->toBe(18);
    expect(User::where('email', 'admin-uji@simapan.test')->count())->toBe(1);
});

test('admin dummy memiliki role administrator setelah seed ulang', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, DevelopmentAdminSeeder::class]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class, DevelopmentAdminSeeder::class]);

    $admin = User::where('email', 'admin-uji@simapan.test')->firstOrFail();

    expect($admin->hasRole('administrator'))->toBeTrue();
});

test('seeder admin menolak bila password kosong', function (): void {
    putenv('SIMAPAN_ADMIN_PASSWORD=');
    $_ENV['SIMAPAN_ADMIN_PASSWORD'] = '';
    $_SERVER['SIMAPAN_ADMIN_PASSWORD'] = '';

    $this->seed([PermissionSeeder::class, RoleSeeder::class, DevelopmentAdminSeeder::class]);
})->throws(RuntimeException::class);
