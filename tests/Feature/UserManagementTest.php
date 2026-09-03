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

test('administrator dapat membuat user dummy dan memberi role valid', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Contoh Anggota Uji',
        'email' => 'anggota-uji@simapan.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_active' => true,
        'roles' => ['field_officer'],
    ]);

    $response->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'anggota-uji@simapan.test')->firstOrFail();
    expect($user->hasRole('field_officer'))->toBeTrue();
});

test('administrator dapat mengedit user lain', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'Nama Diubah Uji',
        'email' => $user->email,
        'is_active' => true,
        'roles' => ['field_supervisor'],
    ]);

    $response->assertRedirect(route('admin.users.index'));
    expect($user->refresh()->name)->toBe('Nama Diubah Uji');
    expect($user->hasRole('field_supervisor'))->toBeTrue();
});

test('email duplikat ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $existing = User::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Contoh Ganda Uji',
        'email' => $existing->email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_active' => true,
        'roles' => ['viewer'],
    ]);

    $response->assertSessionHasErrors('email');
});

test('role tidak valid ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Contoh Nakal Uji',
        'email' => 'nakal-uji@simapan.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_active' => true,
        'roles' => ['superadmin'],
    ]);

    $response->assertSessionHasErrors('roles.0');
    expect(User::where('email', 'nakal-uji@simapan.test')->exists())->toBeFalse();
});

test('user nonaktif tidak dapat login', function (): void {
    $user = User::factory()->create(['is_active' => false]);
    $user->assignRole('viewer');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('user baru non-admin dapat login dan membuka dashboard tetapi tidak halaman admin', function (): void {
    $user = User::factory()->create();
    $user->assignRole('field_officer');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->get('/dashboard')->assertOk();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/roles')->assertForbidden();
});
