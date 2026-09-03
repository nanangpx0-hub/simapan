<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

test('administrator tidak dapat mengubah role dirinya sendiri', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'is_active' => true,
        'roles' => ['viewer'],
    ]);

    $response->assertForbidden();
    expect($admin->refresh()->hasRole('administrator'))->toBeTrue();
});

test('administrator tidak dapat menonaktifkan dirinya sendiri', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'is_active' => false,
        'roles' => ['administrator'],
    ]);

    $response->assertForbidden();
    expect($admin->refresh()->is_active)->toBeTrue();
});

test('administrator tidak dapat menghapus dirinya sendiri', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

    $response->assertForbidden();
    expect(User::where('id', $admin->id)->exists())->toBeTrue();
});
