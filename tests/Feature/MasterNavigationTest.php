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

test('menu master tersembunyi tanpa permission view', function (): void {
    $user = User::factory()->create();
    $user->assignRole('field_officer');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertDontSee('Jenis Survei', false);
    $response->assertDontSee('Unit Kerja', false);
});

test('menu jenis survei tampil dengan permission view', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.view', 'master.survey_type.view');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Jenis Survei', false);
});

test('menu unit kerja tampil dengan permission view', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.view', 'master.work_unit.view');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Unit Kerja', false);
});

test('url langsung tetap 403 tanpa permission master', function (): void {
    $user = User::factory()->create();
    $user->assignRole('field_officer');

    $this->actingAs($user)->get('/master/jenis-survei')->assertForbidden();
    $this->actingAs($user)->get('/master/unit-kerja')->assertForbidden();
});
