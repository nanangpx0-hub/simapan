<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyTypeSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        RegionSeeder::class,
    ]);
});

function payloadAlokasiUji(SurveyPeriod $period, Region $desa, array $overrides = []): array
{
    return array_merge([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => 'NKS-VALID-001',
        'sls_code' => 'SLS-VALID-01',
        'sub_sls_code' => '0',
        'sls_name' => 'SLS Valid Uji',
        'notes' => null,
    ], $overrides);
}

function periodeValidUji(string $code = 'VAL-PER-01'): SurveyPeriod
{
    $admin = User::factory()->create();
    $type = SurveyType::where('code', 'SUSENAS')->firstOrFail();

    return SurveyPeriod::create([
        'code' => $code,
        'survey_type_id' => $type->getKey(),
        'name' => 'Periode Valid Uji',
        'period_type' => 'SEMESTER',
        'period_number' => 1,
        'year' => 2099,
        'start_date' => '2099-01-01',
        'end_date' => '2099-06-30',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
}

function desaValidUji(): Region
{
    return Region::where('full_code', '9901001001')->firstOrFail();
}

test('admin dapat membuat allocation draft valid', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji(periodeValidUji(), desaValidUji(), ['nks' => 'NKS-BUAT-001'])
    );

    $response->assertRedirect(route('allocations.index'));

    $alokasi = Allocation::where('nks', 'NKS-BUAT-001')->firstOrFail();
    expect($alokasi->status)->toBe('DRAFT');
    expect((int) $alokasi->created_by)->toBe((int) $admin->getKey());
});

test('nks duplikat pada periode sama ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-02');
    $desa = desaValidUji();

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $desa, ['nks' => 'NKS-GANDA-001'])
    )->assertRedirect(route('allocations.index'));

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $desa, ['nks' => 'NKS-GANDA-001'])
    )->assertSessionHasErrors('nks');

    expect(Allocation::where('nks', 'NKS-GANDA-001')->count())->toBe(1);
});

test('nks sama pada periode berbeda diizinkan', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $desa = desaValidUji();

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji(periodeValidUji('VAL-PER-03'), $desa, ['nks' => 'NKS-SAMA-001'])
    )->assertRedirect(route('allocations.index'));

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji(periodeValidUji('VAL-PER-04'), $desa, ['nks' => 'NKS-SAMA-001'])
    )->assertRedirect(route('allocations.index'));

    expect(Allocation::where('nks', 'NKS-SAMA-001')->count())->toBe(2);
});

test('format nks sls subsls invalid ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-05');
    $desa = desaValidUji();

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $desa, ['nks' => 'ada spasi'])
    )->assertSessionHasErrors('nks');

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $desa, ['nks' => 'NKS-FMT-001', 'sls_code' => 'simbol!'])
    )->assertSessionHasErrors('sls_code');

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $desa, ['nks' => 'NKS-FMT-002', 'sub_sls_code' => 'titik.koma'])
    )->assertSessionHasErrors('sub_sls_code');

    expect(Allocation::count())->toBe(0);
});

test('village level salah ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-06');
    $kecamatan = Region::where('full_code', '9901001')->firstOrFail();

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $kecamatan, ['nks' => 'NKS-LVL-001'])
    )->assertSessionHasErrors('village_region_id');

    expect(Allocation::where('nks', 'NKS-LVL-001')->exists())->toBeFalse();
});

test('village nonaktif ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-07');
    $desa = desaValidUji();
    $desa->update(['is_active' => false]);

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $desa, ['nks' => 'NKS-MATI-001'])
    )->assertSessionHasErrors('village_region_id');

    expect(Allocation::where('nks', 'NKS-MATI-001')->exists())->toBeFalse();
});

test('survey type tidak dapat dimasukkan bebas', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-08');
    $desa = desaValidUji();

    $this->actingAs($admin)->post(
        route('allocations.store'),
        payloadAlokasiUji($period, $desa, ['nks' => 'NKS-TIPE-001', 'survey_type_id' => 999])
    )->assertSessionHasErrors('survey_type_id');

    expect(Allocation::where('nks', 'NKS-TIPE-001')->exists())->toBeFalse();
});

test('nks sls subsls immutable saat update', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-09');
    $desa = desaValidUji();
    $alokasi = Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => 'NKS-KEKAL-001',
        'sls_code' => 'SLS-KEKAL-01',
        'sub_sls_code' => '0',
        'sls_name' => 'Kekal Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);

    $base = ['sls_name' => 'Kekal Uji'];

    foreach ([
        ['nks' => 'NKS-GANTI-001'],
        ['sls_code' => 'SLS-GANTI-01'],
        ['sub_sls_code' => '9'],
        ['survey_period_id' => $period->getKey() + 999],
        ['village_region_id' => $desa->getKey() + 999],
    ] as $tamper) {
        $this->actingAs($admin)->put(
            route('allocations.update', $alokasi),
            array_merge($base, $tamper)
        )->assertSessionHasErrors(array_key_first($tamper));
    }

    $alokasi->refresh();
    expect($alokasi->nks)->toBe('NKS-KEKAL-001');
    expect($alokasi->sls_code)->toBe('SLS-KEKAL-01');
});

test('aturan edit per status ditegakkan', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-10');
    $desa = desaValidUji();

    foreach (['SUSPENDED', 'COMPLETED', 'ARCHIVED'] as $status) {
        $alokasi = Allocation::create([
            'survey_period_id' => $period->getKey(),
            'village_region_id' => $desa->getKey(),
            'nks' => 'NKS-BEKU-'.$status,
            'sls_name' => 'Beku Uji',
            'status' => $status,
            'created_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)->put(route('allocations.update', $alokasi), [
            'sls_name' => 'Coba Ubah Uji',
        ])->assertSessionHasErrors('status');

        expect($alokasi->refresh()->sls_name)->toBe('Beku Uji');
    }

    $draft = Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => 'NKS-DRAFT-001',
        'sls_name' => 'Draft Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->put(route('allocations.update', $draft), [
        'sls_name' => 'Draft Ubah Uji',
    ])->assertRedirect(route('allocations.index'));

    expect($draft->refresh()->sls_name)->toBe('Draft Ubah Uji');
});

test('archived tidak dapat diedit', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-11');
    $desa = desaValidUji();
    $alokasi = Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => 'NKS-ARSIP-001',
        'sls_name' => 'Arsip Uji',
        'status' => 'ARCHIVED',
        'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->get(route('allocations.edit', $alokasi))->assertForbidden();
});

test('tidak ada endpoint delete alokasi', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = periodeValidUji('VAL-PER-12');
    $desa = desaValidUji();
    $alokasi = Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => 'NKS-HAPUS-001',
        'sls_name' => 'Hapus Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->delete('/alokasi/'.$alokasi->id)->assertStatus(405);
    expect(Allocation::where('nks', 'NKS-HAPUS-001')->exists())->toBeTrue();
});
