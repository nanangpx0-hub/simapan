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

test('menu periode survei tersembunyi tanpa permission view', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertDontSee('Periode Survei', false);
});

test('menu periode survei tampil dengan permission view', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.view', 'master.survey_period.view');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Periode Survei', false);
});

test('url langsung tetap 403 tanpa permission periode', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)->get('/master/periode-survei')->assertForbidden();
    $this->actingAs($user)->get('/master/periode-survei/create')->assertForbidden();
});
