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

test('guest diarahkan ke login saat membuka profile', function (): void {
    $this->get('/profile')->assertRedirect('/login');
});

test('user tanpa profile.manage mendapat 403', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertForbidden();
    $this->actingAs($user)->patch('/profile', [
        'name' => 'Contoh Uji',
        'email' => $user->email,
    ])->assertForbidden();
    $this->actingAs($user)->delete('/profile', [
        'password' => 'password',
    ])->assertForbidden();
});

test('user dengan profile.manage dapat membuka profile', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)->get('/profile')->assertOk();
});

test('setiap role sistem dapat membuka profile', function (): void {
    foreach (array_keys(config('simapan_roles.roles', [])) as $slug) {
        $user = User::factory()->create();
        $user->assignRole($slug);

        $this->actingAs($user)->get('/profile')->assertOk();
    }
});

test('administrator dapat membuka profile', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $this->actingAs($admin)->get('/profile')->assertOk();
});
