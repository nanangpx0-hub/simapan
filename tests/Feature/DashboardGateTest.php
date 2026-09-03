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

test('guest diarahkan ke login saat membuka dashboard', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('user login tanpa dashboard.view mendapat 403', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertForbidden();
});

test('setiap role sistem dapat membuka dashboard', function (): void {
    foreach (array_keys(config('simapan_roles.roles', [])) as $slug) {
        $user = User::factory()->create();
        $user->assignRole($slug);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('SIMAPAN');
        $response->assertSee($slug);
    }
});

test('dashboard menampilkan identitas dan role pengguna', function (): void {
    $user = User::factory()->create(['name' => 'Contoh Pengguna Uji']);
    $user->assignRole('field_officer');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Sistem Informasi Manajemen Pengolahan dan Pengawasan');
    $response->assertSee('Dari lapangan hingga data final.');
    $response->assertSee('Contoh Pengguna Uji');
});
