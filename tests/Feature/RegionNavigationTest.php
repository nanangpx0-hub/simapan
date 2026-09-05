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

test('menu master wilayah tersembunyi tanpa permission view', function (): void {
    $user = User::factory()->create();
    $user->assignRole('field_officer');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertDontSee('Master Wilayah', false);
});

test('menu master wilayah tampil dengan permission view', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.view', 'master.region.view');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Master Wilayah', false);
});

test('url langsung tetap 403 tanpa permission wilayah', function (): void {
    $user = User::factory()->create();
    $user->assignRole('field_officer');

    $this->actingAs($user)->get('/master/wilayah')->assertForbidden();
    $this->actingAs($user)->get('/master/wilayah/create')->assertForbidden();
});
