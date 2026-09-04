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
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertDontSee('Dokumen', false);

    $allowed = User::factory()->create();
    $allowed->givePermissionTo('dashboard.view', 'document.view');

    $this->actingAs($allowed)->get('/dashboard')->assertOk()->assertSee('Dokumen', false);
    $this->actingAs($viewer)->get('/dokumen')->assertForbidden();
});
