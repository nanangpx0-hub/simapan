<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\Officer;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\OfficerSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;
use Database\Seeders\WorkUnitSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        WorkUnitSeeder::class,
        RegionSeeder::class,
        SurveyPeriodSeeder::class,
        OfficerSeeder::class,
    ]);
});

function alokasiRiwayatUji(User $admin, string $nks = 'NKS-RIW-001', string $status = 'DRAFT'): Allocation
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'Riwayat Uji',
        'status' => $status,
        'created_by' => $admin->getKey(),
    ]);
}

function petugasRiwayatUji(string $code, string $unit = 'SOSIAL'): Officer
{
    $workUnit = WorkUnit::where('code', $unit)->firstOrFail();

    return Officer::create([
        'code' => $code, 'name' => 'Riwayat Uji '.$code, 'work_unit_id' => $workUnit->getKey(),
    ]);
}

test('assignment petugas valid sesuai unit kerja', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiRiwayatUji($admin);
    $officer = petugasRiwayatUji('RIW-PCL-01');

    $response = $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $officer->id,
        'assignment_role' => 'FIELD_OFFICER',
        'employment_category' => 'MITRA',
    ]);

    $response->assertRedirect(route('allocations.assignments.index', $alokasi));

    $assignment = Assignment::where('allocation_id', $alokasi->id)->where('assignment_role', 'FIELD_OFFICER')->firstOrFail();
    expect($assignment->is_active)->toBeTrue();
    expect($assignment->employment_category)->toBe('MITRA');
    expect($assignment->started_at)->not->toBeNull();
    expect((int) $assignment->assigned_by)->toBe((int) $admin->getKey());
});

test('petugas unit salah ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiRiwayatUji($admin, 'NKS-RIW-002');
    $olah = petugasRiwayatUji('RIW-OLAH-01', 'PENGOLAHAN_LS');

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $olah->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertSessionHasErrors('officer_id');

    expect(Assignment::where('allocation_id', $alokasi->id)->count())->toBe(0);
});

test('petugas inactive atau terhapus ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiRiwayatUji($admin, 'NKS-RIW-003');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

    $inactive = Officer::create([
        'code' => 'RIW-MATI-01', 'name' => 'Mati Uji', 'work_unit_id' => $unit->getKey(), 'status' => 'INACTIVE',
    ]);

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $inactive->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertSessionHasErrors('officer_id');

    $deleted = petugasRiwayatUji('RIW-HAPUS-01');
    $deleted->delete();

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $deleted->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertSessionHasErrors('officer_id');

    expect(Assignment::where('allocation_id', $alokasi->id)->count())->toBe(0);
});

test('satu active assignment per role dan reassign menutup lama', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiRiwayatUji($admin, 'NKS-RIW-004');
    $lama = petugasRiwayatUji('RIW-LAMA-01');
    $baru = petugasRiwayatUji('RIW-BARU-01');

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $lama->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $baru->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertRedirect();

    $rows = Assignment::where('allocation_id', $alokasi->id)->where('assignment_role', 'FIELD_OFFICER')->orderBy('id')->get();

    expect($rows)->toHaveCount(2);
    expect($rows[0]->is_active)->toBeFalse();
    expect($rows[0]->ended_at)->not->toBeNull();
    expect($rows[1]->is_active)->toBeTrue();
    expect((int) $rows[1]->officer_id)->toBe((int) $baru->id);
    expect(Assignment::where('allocation_id', $alokasi->id)->where('assignment_role', 'FIELD_OFFICER')->active()->count())->toBe(1);
});

test('employment category organik mitra null valid', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiRiwayatUji($admin, 'NKS-RIW-005');

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => petugasRiwayatUji('RIW-KAT-01')->id,
        'assignment_role' => 'FIELD_OFFICER',
        'employment_category' => 'ORGANIK',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => petugasRiwayatUji('RIW-KAT-02', 'PENGOLAHAN_LS')->id,
        'assignment_role' => 'PROCESSING_OFFICER',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => petugasRiwayatUji('RIW-KAT-03')->id,
        'assignment_role' => 'FIELD_SUPERVISOR',
        'employment_category' => 'KONTRAK',
    ])->assertSessionHasErrors('employment_category');
});

test('assignment beku pada completed dan archived', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    foreach (['COMPLETED', 'ARCHIVED'] as $status) {
        $alokasi = alokasiRiwayatUji($admin, 'NKS-BEKU-'.$status, $status);

        $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
            'officer_id' => petugasRiwayatUji('RIW-BEKU-'.$status)->id,
            'assignment_role' => 'FIELD_OFFICER',
        ])->assertSessionHasErrors('allocation');
    }
});

test('unassign menonaktifkan dengan ended_at', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiRiwayatUji($admin, 'NKS-RIW-006');
    $officer = petugasRiwayatUji('RIW-UN-01');

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $officer->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertRedirect();

    $assignment = Assignment::where('allocation_id', $alokasi->id)->where('assignment_role', 'FIELD_OFFICER')->firstOrFail();

    $this->actingAs($admin)->post(route('allocations.assignments.unassign', [$alokasi, $assignment]))->assertRedirect();

    $assignment->refresh();
    expect($assignment->is_active)->toBeFalse();
    expect($assignment->ended_at)->not->toBeNull();

    $this->actingAs($admin)->post(route('allocations.assignments.unassign', [$alokasi, $assignment]))->assertSessionHasErrors('assignment');
});

test('aktivasi gagal bila role utama tidak lengkap', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $period->update(['status' => 'ACTIVE']);

    $alokasi = alokasiRiwayatUji($admin, 'NKS-RIW-007');
    $officer = petugasRiwayatUji('RIW-SEB-01');

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $officer->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('allocations.activate', $alokasi))->assertSessionHasErrors('status');

    expect($alokasi->refresh()->status)->toBe('DRAFT');
});
