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

test('administrator aktif terakhir tidak dapat dicabut role-nya', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'is_active' => true,
        'roles' => ['viewer'],
    ]);

    $response->assertForbidden();
});

test('administrator aktif terakhir tidak dapat dinonaktifkan atau dihapus', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'is_active' => false,
        'roles' => ['administrator'],
    ])->assertForbidden();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();

    expect(User::where('is_active', true)->role('administrator')->count())->toBe(1);
});

test('dengan dua administrator aktif, penghapusan admin lain diizinkan', function (): void {
    $adminA = User::factory()->create();
    $adminA->assignRole('administrator');
    $adminB = User::factory()->create();
    $adminB->assignRole('administrator');

    $this->actingAs($adminA)->delete(route('admin.users.destroy', $adminB))->assertRedirect(route('admin.users.index'));

    expect(User::where('id', $adminB->id)->exists())->toBeFalse();
    expect(User::where('is_active', true)->role('administrator')->count())->toBe(1);
});

test('dengan dua administrator aktif, penonaktifan admin lain diizinkan', function (): void {
    $adminA = User::factory()->create();
    $adminA->assignRole('administrator');
    $adminB = User::factory()->create();
    $adminB->assignRole('administrator');

    $response = $this->actingAs($adminA)->put(route('admin.users.update', $adminB), [
        'name' => $adminB->name,
        'email' => $adminB->email,
        'is_active' => false,
        'roles' => ['administrator'],
    ]);

    $response->assertRedirect(route('admin.users.index'));
    expect($adminB->refresh()->is_active)->toBeFalse();
    expect(User::where('is_active', true)->role('administrator')->count())->toBe(1);
});
