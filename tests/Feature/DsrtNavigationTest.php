<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        RegionSeeder::class,
        SurveyPeriodSeeder::class,
    ]);
});

test('link dsrt mengikuti permission dan jenis allocation', function (): void {
    $admin = adminDsrtUji();
    $susenas = alokasiSusenasUji($admin, 'NKS-DSRT-NAV-01');
    $seruti = alokasiSerutiUji($admin, 'NKS-DSRT-NAV-02');

    $this->actingAs($admin)->get(route('allocations.show', $susenas))->assertOk()->assertSee('DSRT Susenas', false);
    $this->actingAs($admin)->get(route('allocations.show', $seruti))->assertOk()->assertDontSee('DSRT Susenas', false);
});

test('akses url dsrt langsung tetap 403 tanpa permission', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-NAV-03');

    $user = User::factory()->create();
    $user->givePermissionTo('allocation.view');

    $this->actingAs($user)->get(route('allocations.dsrt.index', $alokasi))->assertForbidden();
});
