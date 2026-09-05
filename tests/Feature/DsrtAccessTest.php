<?php

declare(strict_types=1);

use App\Models\DsrtSample;
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

test('guest diarahkan ke login pada dsrt', function (): void {
    $this->get('/alokasi/1/dsrt')->assertRedirect('/login');
    $this->get('/alokasi/1/dsrt/create')->assertRedirect('/login');
});

test('user tanpa dsrt.view mendapat 403', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin);
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-VW-01', 'nurt' => 'NURT-VW-01', 'krt_name' => 'KRT Vw Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $user = User::factory()->create();
    $user->assignRole('processing_officer');

    $this->actingAs($user)->get(route('allocations.dsrt.index', $alokasi))->assertForbidden();
    $this->actingAs($user)->get(route('allocations.dsrt.show', [$alokasi, $sample]))->assertForbidden();
});

test('user tanpa dsrt.manage mendapat 403 tulis', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin);

    $user = User::factory()->create();
    $user->givePermissionTo('dsrt.view');

    $this->actingAs($user)->get(route('allocations.dsrt.create', $alokasi))->assertForbidden();
    $this->actingAs($user)->post(route('allocations.dsrt.store', $alokasi), [
        'nus' => 'NUS-01', 'nurt' => 'NURT-01', 'krt_name' => 'KRT Uji',
    ])->assertForbidden();
});

test('user tanpa dsrt.verify mendapat 403 verify dan archive', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin);
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-VF-01', 'nurt' => 'NURT-VF-01', 'krt_name' => 'KRT Vf Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('dsrt.view', 'dsrt.manage');

    $this->actingAs($user)->post(route('allocations.dsrt.verify', [$alokasi, $sample]))->assertForbidden();
    $this->actingAs($user)->post(route('allocations.dsrt.archive', [$alokasi, $sample]))->assertForbidden();
    expect($sample->refresh()->record_status)->toBe('DRAFT');
});

test('administrator dapat mengakses semua dsrt', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin);

    $this->actingAs($admin)->get(route('allocations.dsrt.index', $alokasi))->assertOk();
    $this->actingAs($admin)->get(route('allocations.dsrt.create', $alokasi))->assertOk();
});

test('link dsrt hanya pada allocation susenas dengan dsrt.view', function (): void {
    $admin = adminDsrtUji();
    $susenas = alokasiSusenasUji($admin, 'NKS-DSRT-LINK-01');
    $seruti = alokasiSerutiUji($admin);

    $this->actingAs($admin)->get(route('allocations.show', $susenas))->assertOk()->assertSee('DSRT Susenas', false);
    $this->actingAs($admin)->get(route('allocations.show', $seruti))->assertOk()->assertDontSee('DSRT Susenas', false);

    $denied = User::factory()->create();
    $denied->assignRole('processing_officer');

    $this->actingAs($denied)->get(route('allocations.show', $susenas))->assertForbidden();
});
