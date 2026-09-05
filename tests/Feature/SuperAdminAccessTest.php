<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');
});

test('super admin dapat mengakses seluruh halaman modul tanpa batasan', function (string $routeName, array $params = []): void {
    $response = $this->actingAs($this->superAdmin)->get(route($routeName, $params));

    $response->assertOk();
})->with([
    'dashboard' => ['dashboard'],
    'users index' => ['admin.users.index'],
    'users create' => ['admin.users.create'],
    'roles index' => ['admin.roles.index'],
    'roles create' => ['admin.roles.create'],
    'audit index' => ['audit_logs.index'],
    'jenis survei' => ['master.jenis-survei.index'],
    'unit kerja' => ['master.unit-kerja.index'],
    'periode survei' => ['master.survey_periods.index'],
    'wilayah' => ['master.wilayah.index'],
    'petugas' => ['master.officers.index'],
    'alokasi' => ['allocations.index'],
    'dokumen' => ['documents.index'],
    'manifest' => ['document_manifests.index'],
]);

test('super admin dapat membuka form kelola role katalog', function (): void {
    $role = Role::query()->where('name', 'administrator')->firstOrFail();

    $this->actingAs($this->superAdmin)
        ->get(route('admin.roles.edit', $role))
        ->assertOk();
});

test('peran lain hanya dapat mengakses sesuai izin', function (string $roleSlug, string $routeName, array $params = []): void {
    $user = User::factory()->create();
    $user->assignRole($roleSlug);

    $this->actingAs($user)
        ->get(route($routeName, $params))
        ->assertForbidden();
})->with([
    'viewer ke users' => ['viewer', 'admin.users.index'],
    'field_officer ke roles' => ['field_officer', 'admin.roles.index'],
    'field_officer ke audit' => ['field_officer', 'audit_logs.index'],
    'viewer ke petugas manage' => ['viewer', 'master.officers.create'],
    'administrator ke roles create' => ['administrator', 'admin.roles.create'],
    'viewer ke users create' => ['viewer', 'admin.users.create'],
]);