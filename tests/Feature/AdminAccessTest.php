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

test('guest diarahkan ke login pada halaman admin', function (): void {
    $this->get('/admin/users')->assertRedirect('/login');
    $this->get('/admin/roles')->assertRedirect('/login');
});

test('user tanpa permission admin mendapat 403', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
    $this->actingAs($user)->get('/admin/roles')->assertForbidden();
});

test('administrator mendapat 200 pada halaman admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $this->actingAs($admin)->get('/admin/users')->assertOk();
    $this->actingAs($admin)->get('/admin/roles')->assertOk();
});
