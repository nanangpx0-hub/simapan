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

test('menu dokumen mengikuti permission view', function (): void {
    $denied = User::factory()->create();
    $denied->givePermissionTo('dashboard.view', 'profile.manage');

    $this->actingAs($denied)->get('/dashboard')->assertOk()->assertDontSee('Dokumen', false);

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertSee('Dokumen', false);
    $this->actingAs($denied)->get('/dokumen')->assertForbidden();
});
