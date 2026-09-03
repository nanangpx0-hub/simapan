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

test('dsrt dapat dikelola pada allocation susenas valid', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-GATE-01');

    $this->actingAs($admin)->get(route('allocations.dsrt.index', $alokasi))->assertOk();
    $this->actingAs($admin)->post(route('allocations.dsrt.store', $alokasi), [
        'nus' => 'NUS-GATE-01', 'nurt' => 'NURT-GATE-01', 'krt_name' => 'KRT Gate Uji',
    ])->assertRedirect(route('allocations.dsrt.index', $alokasi));

    expect(DsrtSample::where('nus', 'NUS-GATE-01')->exists())->toBeTrue();
});

test('semua dsrt route dan action pada seruti ditolak 422', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSerutiUji($admin, 'NKS-DSRT-GATE-02');

    $this->actingAs($admin)->get(route('allocations.dsrt.index', $alokasi))->assertStatus(422);
    $this->actingAs($admin)->get(route('allocations.dsrt.create', $alokasi))->assertStatus(422);
    $this->actingAs($admin)->post(route('allocations.dsrt.store', $alokasi), [
        'nus' => 'NUS-GATE-02', 'nurt' => 'NURT-GATE-02', 'krt_name' => 'KRT Gate Uji',
    ])->assertSessionHasErrors('allocation');

    expect(DsrtSample::count())->toBe(0);
});

test('nested dsrt bukan milik allocation menghasilkan 404', function (): void {
    $admin = adminDsrtUji();
    $alokasiA = alokasiSusenasUji($admin, 'NKS-DSRT-GATE-03');
    $alokasiB = alokasiSusenasUji($admin, 'NKS-DSRT-GATE-04');

    $sample = DsrtSample::create([
        'allocation_id' => $alokasiA->getKey(),
        'nus' => 'NUS-GATE-03', 'nurt' => 'NURT-GATE-03', 'krt_name' => 'KRT Gate Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->get(route('allocations.dsrt.show', [$alokasiB, $sample]))->assertNotFound();
    $this->actingAs($admin)->get(route('allocations.dsrt.edit', [$alokasiB, $sample]))->assertNotFound();
    $this->actingAs($admin)->put(route('allocations.dsrt.update', [$alokasiB, $sample]), [
        'krt_name' => 'Ubah Uji', 'enumeration_status' => 'PENDING',
    ])->assertNotFound();
    $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasiB, $sample]))->assertNotFound();
});
