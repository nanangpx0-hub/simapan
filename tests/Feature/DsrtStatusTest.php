<?php

declare(strict_types=1);

use App\Models\DsrtSample;
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

test('draft dapat verify bila syarat lengkap', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-ST-01');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-ST-01', 'nurt' => 'NURT-ST-01', 'krt_name' => 'KRT St Uji',
        'enumeration_status' => 'COMPLETED', 'notes' => 'Selesai uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $response = $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasi, $sample]));

    $response->assertRedirect(route('allocations.dsrt.show', [$alokasi, $sample]));
    expect($sample->refresh()->record_status)->toBe('VERIFIED');
});

test('draft dapat archive', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-ST-02');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-ST-02', 'nurt' => 'NURT-ST-02', 'krt_name' => 'KRT St Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('allocations.dsrt.archive', [$alokasi, $sample]))->assertRedirect();

    $sample->refresh();
    expect($sample->record_status)->toBe('ARCHIVED');
    expect((int) $sample->archived_by)->toBe((int) $admin->getKey());
    expect($sample->archived_at)->not->toBeNull();
});

test('verified dapat archive', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-ST-03');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-ST-03', 'nurt' => 'NURT-ST-03', 'krt_name' => 'KRT St Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasi, $sample]))->assertRedirect();
    $this->actingAs($admin)->post(route('allocations.dsrt.archive', [$alokasi, $sample]))->assertRedirect();

    expect($sample->refresh()->record_status)->toBe('ARCHIVED');
});

test('transisi invalid ditolak', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-ST-04');

    $verified = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-ST-04', 'nurt' => 'NURT-ST-04', 'krt_name' => 'KRT St Uji',
        'record_status' => 'VERIFIED', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasi, $verified]))
        ->assertSessionHasErrors('record_status');

    $archived = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-ST-05', 'nurt' => 'NURT-ST-05', 'krt_name' => 'KRT St Uji',
        'record_status' => 'ARCHIVED', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasi, $archived]))
        ->assertSessionHasErrors('record_status');
    $this->actingAs($admin)->post(route('allocations.dsrt.archive', [$alokasi, $archived]))
        ->assertSessionHasErrors('record_status');
});

test('verify mengisi verified_by dan verified_at server-side', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-ST-06');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-ST-06', 'nurt' => 'NURT-ST-06', 'krt_name' => 'KRT St Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    expect($sample->verified_by)->toBeNull();
    expect($sample->verified_at)->toBeNull();

    $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasi, $sample]))->assertRedirect();

    $sample->refresh();
    expect((int) $sample->verified_by)->toBe((int) $admin->getKey());
    expect($sample->verified_at)->not->toBeNull();
});

test('archived final dan beku', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-ST-07');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-ST-07', 'nurt' => 'NURT-ST-07', 'krt_name' => 'KRT St Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('allocations.dsrt.archive', [$alokasi, $sample]))->assertRedirect();

    $this->actingAs($admin)->put(route('allocations.dsrt.update', [$alokasi, $sample]), [
        'krt_name' => 'Coba Ubah Uji', 'enumeration_status' => 'PENDING',
    ])->assertSessionHasErrors('record_status');

    expect($sample->refresh()->record_status)->toBe('ARCHIVED');
    expect($sample->refresh()->krt_name)->toBe('KRT St Uji');
});
