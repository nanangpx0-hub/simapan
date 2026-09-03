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

function payloadDsrtUji(array $overrides = []): array
{
    return array_merge([
        'nus' => 'NUS-VALID-01',
        'nurt' => 'NURT-VALID-01',
        'family_number' => 'KK-01',
        'building_number' => 'B-01',
        'household_number' => 'RT-01',
        'krt_name' => 'KRT Valid Uji',
        'address' => 'Jalan Contoh Uji 1',
        'krt_education_code' => '02',
        'enumeration_status' => 'PENDING',
        'contact_person' => 'Kontak Uji',
        'contact_phone' => '0800000001',
        'notes' => 'Catatan uji',
    ], $overrides);
}

test('create draft valid', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-01');

    $response = $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-VAL-01', 'nurt' => 'NURT-VAL-01'])
    );

    $response->assertRedirect(route('allocations.dsrt.index', $alokasi));

    $sample = DsrtSample::where('nus', 'NUS-VAL-01')->firstOrFail();
    expect($sample->record_status)->toBe('DRAFT');
    expect((int) $sample->created_by)->toBe((int) $admin->getKey());
});

test('nus duplikat dalam allocation ditolak', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-02');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-DUP-01', 'nurt' => 'NURT-DUP-01'])
    )->assertRedirect();

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-DUP-01', 'nurt' => 'NURT-DUP-02'])
    )->assertSessionHasErrors('nus');

    expect(DsrtSample::where('nus', 'NUS-DUP-01')->count())->toBe(1);
});

test('nurt duplikat dalam allocation ditolak', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-03');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-DUP-03', 'nurt' => 'NURT-DUP-03'])
    )->assertRedirect();

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-DUP-04', 'nurt' => 'NURT-DUP-03'])
    )->assertSessionHasErrors('nurt');
});

test('nus nurt sama pada allocation berbeda diizinkan', function (): void {
    $admin = adminDsrtUji();
    $alokasiA = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-04');
    $alokasiB = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-05');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasiA),
        payloadDsrtUji(['nus' => 'NUS-SAMA-01', 'nurt' => 'NURT-SAMA-01'])
    )->assertRedirect();

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasiB),
        payloadDsrtUji(['nus' => 'NUS-SAMA-01', 'nurt' => 'NURT-SAMA-01'])
    )->assertRedirect();

    expect(DsrtSample::where('nus', 'NUS-SAMA-01')->count())->toBe(2);
});

test('format kode dan nomor invalid ditolak', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-06');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'ada spasi', 'nurt' => 'NURT-FMT-01'])
    )->assertSessionHasErrors('nus');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-FMT-01', 'nurt' => 'simbol!', 'family_number' => 'titik.koma'])
    )->assertSessionHasErrors('nurt');

    expect(DsrtSample::count())->toBe(0);
});

test('nus nurt immutable saat update', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-07');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-KEKAL-01', 'nurt' => 'NURT-KEKAL-01', 'krt_name' => 'KRT Kekal Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $base = ['krt_name' => 'KRT Kekal Uji', 'enumeration_status' => 'PENDING'];

    $this->actingAs($admin)->put(
        route('allocations.dsrt.update', [$alokasi, $sample]),
        array_merge($base, ['nus' => 'NUS-GANTI-01'])
    )->assertSessionHasErrors('nus');

    $this->actingAs($admin)->put(
        route('allocations.dsrt.update', [$alokasi, $sample]),
        array_merge($base, ['nurt' => 'NURT-GANTI-01'])
    )->assertSessionHasErrors('nurt');

    $sample->refresh();
    expect($sample->nus)->toBe('NUS-KEKAL-01');
    expect($sample->nurt)->toBe('NURT-KEKAL-01');
});

test('krt wajib dan enumeration invalid ditolak', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-08');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-WJB-01', 'nurt' => 'NURT-WJB-01', 'krt_name' => ''])
    )->assertSessionHasErrors('krt_name');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-WJB-02', 'nurt' => 'NURT-WJB-02', 'enumeration_status' => 'SELESAI'])
    )->assertSessionHasErrors('enumeration_status');

    expect(DsrtSample::count())->toBe(0);
});

test('notes wajib untuk status khusus', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-09');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-NOTE-01', 'nurt' => 'NURT-NOTE-01', 'enumeration_status' => 'MOVED', 'notes' => null])
    )->assertSessionHasErrors('notes');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-NOTE-01', 'nurt' => 'NURT-NOTE-01', 'enumeration_status' => 'MOVED', 'notes' => 'Pindah alamat uji'])
    )->assertRedirect();

    expect(DsrtSample::where('nus', 'NUS-NOTE-01')->exists())->toBeTrue();
});

test('record status dan audit fields prohibited', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-10');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-PRO-01', 'nurt' => 'NURT-PRO-01', 'record_status' => 'VERIFIED'])
    )->assertSessionHasErrors('record_status');

    $this->actingAs($admin)->post(
        route('allocations.dsrt.store', $alokasi),
        payloadDsrtUji(['nus' => 'NUS-PRO-02', 'nurt' => 'NURT-PRO-02', 'verified_by' => $admin->id])
    )->assertSessionHasErrors('verified_by');

    expect(DsrtSample::count())->toBe(0);
});

test('allocation completed atau archived menolak create dan edit', function (): void {
    $admin = adminDsrtUji();

    foreach (['COMPLETED', 'ARCHIVED'] as $status) {
        $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-'.$status);
        $alokasi->update(['status' => $status]);

        $this->actingAs($admin)->post(
            route('allocations.dsrt.store', $alokasi),
            payloadDsrtUji(['nus' => 'NUS-BEKU-'.$status, 'nurt' => 'NURT-BEKU-'.$status])
        )->assertSessionHasErrors('allocation');

        $sample = DsrtSample::create([
            'allocation_id' => $alokasi->getKey(),
            'nus' => 'NUS-ADA-'.$status, 'nurt' => 'NURT-ADA-'.$status, 'krt_name' => 'KRT Ada Uji',
            'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)->put(
            route('allocations.dsrt.update', [$alokasi, $sample]),
            ['krt_name' => 'Ubah Uji', 'enumeration_status' => 'PENDING']
        )->assertSessionHasErrors('allocation');
    }
});

test('verified dan archived menolak edit', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-VAL-11');

    foreach (['VERIFIED', 'ARCHIVED'] as $status) {
        $sample = DsrtSample::create([
            'allocation_id' => $alokasi->getKey(),
            'nus' => 'NUS-BEKU2-'.$status, 'nurt' => 'NURT-BEKU2-'.$status, 'krt_name' => 'KRT Beku Uji',
            'record_status' => $status, 'created_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)->put(
            route('allocations.dsrt.update', [$alokasi, $sample]),
            ['krt_name' => 'Coba Ubah Uji', 'enumeration_status' => 'PENDING']
        )->assertSessionHasErrors('record_status');

        expect($sample->refresh()->krt_name)->toBe('KRT Beku Uji');
    }
});
