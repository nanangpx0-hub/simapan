<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\DsrtSample;
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
use Illuminate\Http\UploadedFile;

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

function sosialOperatorUji(): User
{
    $user = User::factory()->create();
    $user->assignRole('social_operator');

    return $user;
}

function sosialAlokasiUji(User $actor, string $nks = 'NKS-SOS-001'): Allocation
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'SLS Sosial Uji',
        'status' => 'DRAFT',
        'created_by' => $actor->getKey(),
    ]);
}

function sosialPetugasUji(string $code, string $name): Officer
{
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

    return Officer::create([
        'code' => $code,
        'name' => $name,
        'work_unit_id' => $unit->getKey(),
        'status' => 'ACTIVE',
    ]);
}

test('social_operator dapat membuka halaman alokasi dan form create', function (): void {
    $sosial = sosialOperatorUji();

    $this->actingAs($sosial)->get('/alokasi')->assertOk();
    $this->actingAs($sosial)->get('/alokasi/create')->assertOk();
});

test('social_operator dapat membuat alokasi susenas', function (): void {
    $sosial = sosialOperatorUji();
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    $this->actingAs($sosial)->post(route('allocations.store'), [
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => 'NKS-SOS-002',
        'sls_name' => 'SLS Sosial Baru',
    ])->assertRedirect(route('allocations.index'));

    expect(Allocation::where('nks', 'NKS-SOS-002')->exists())->toBeTrue();
});

test('social_operator lolos gate impor alokasi', function (): void {
    $sosial = sosialOperatorUji();

    // File txt ditolak validasi (302) — bukan 403 — artinya izin manage lolos.
    $this->actingAs($sosial)->post(route('allocations.import'), [
        'file' => UploadedFile::fake()->create('data.txt', 10),
    ])->assertSessionHasErrors('file');
});

test('social_operator dapat menugaskan PPL dan PML', function (): void {
    $sosial = sosialOperatorUji();
    $alokasi = sosialAlokasiUji($sosial, 'NKS-SOS-003');
    $ppl = sosialPetugasUji('SOS-PPL-01', 'PPL Sosial Uji');
    $pml = sosialPetugasUji('SOS-PML-01', 'PML Sosial Uji');

    $this->actingAs($sosial)->get(route('allocations.assignments.index', $alokasi))->assertOk();

    $this->actingAs($sosial)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $ppl->getKey(),
        'assignment_role' => 'FIELD_OFFICER',
        'employment_category' => 'MITRA',
    ])->assertRedirect();

    $this->actingAs($sosial)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $pml->getKey(),
        'assignment_role' => 'FIELD_SUPERVISOR',
        'employment_category' => 'ORGANIK',
    ])->assertRedirect();

    expect($alokasi->activeAssignments()->forRole('FIELD_OFFICER')->where('officer_id', $ppl->getKey())->exists())->toBeTrue();
    expect($alokasi->activeAssignments()->forRole('FIELD_SUPERVISOR')->where('officer_id', $pml->getKey())->exists())->toBeTrue();
});

test('penugasan mencatat actor social_operator dan audit log', function (): void {
    $sosial = sosialOperatorUji();
    $alokasi = sosialAlokasiUji($sosial, 'NKS-SOS-004');
    $ppl = sosialPetugasUji('SOS-PPL-02', 'PPL Audit Uji');

    $this->actingAs($sosial)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $ppl->getKey(),
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertRedirect();

    $assignment = Assignment::query()
        ->where('allocation_id', $alokasi->getKey())
        ->where('officer_id', $ppl->getKey())
        ->active()
        ->firstOrFail();

    expect((int) $assignment->assigned_by)->toBe((int) $sosial->getKey());
    expect(AuditLog::query()
        ->where('auditable_type', Assignment::class)
        ->where('auditable_id', $assignment->getKey())
        ->whereIn('action', ['created', 'assigned', 'reassigned'])
        ->exists())->toBeTrue();
});

test('social_operator dapat mengaktifkan alokasi', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $period))->assertRedirect();

    $sosial = sosialOperatorUji();
    $alokasi = sosialAlokasiUji($sosial, 'NKS-SOS-005');

    $ppl = sosialPetugasUji('SOS-PPL-03', 'PPL Aktif Uji');
    $pml = sosialPetugasUji('SOS-PML-03', 'PML Aktif Uji');
    $pengolahan = Officer::where('code', 'OFF-002')->firstOrFail();

    foreach ([
        [$ppl, 'FIELD_OFFICER', 'MITRA'],
        [$pml, 'FIELD_SUPERVISOR', 'ORGANIK'],
        [$pengolahan, 'PROCESSING_OFFICER', 'ORGANIK'],
        [$pengolahan, 'PROCESSING_SUPERVISOR', 'ORGANIK'],
    ] as [$officer, $role, $kategori]) {
        $this->actingAs($sosial)->post(route('allocations.assignments.store', $alokasi), [
            'officer_id' => $officer->getKey(),
            'assignment_role' => $role,
            'employment_category' => $kategori,
        ])->assertRedirect();
    }

    $this->actingAs($sosial)->post(route('allocations.activate', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('ACTIVE');
});

test('social_operator dapat memantau sampel DSRT', function (): void {
    $sosial = sosialOperatorUji();
    $alokasi = sosialAlokasiUji($sosial, 'NKS-SOS-006');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-SOS-01', 'nurt' => 'NURT-SOS-01', 'krt_name' => 'KRT Sosial Uji',
        'record_status' => 'DRAFT', 'created_by' => $sosial->getKey(),
    ]);

    $this->actingAs($sosial)->get(route('allocations.dsrt.index', $alokasi))->assertOk();
    $this->actingAs($sosial)->get(route('allocations.dsrt.show', [$alokasi, $sample]))->assertOk();
});

test('social_operator melihat menu unit kerja dan kartu pemantauan sosial', function (): void {
    $sosial = sosialOperatorUji();

    $this->actingAs($sosial)->get('/master/unit-kerja')->assertOk();

    $this->actingAs($sosial)->get('/dashboard')->assertOk()
        ->assertSee('Unit Kerja', false)
        ->assertSee('Pemantauan Tim Sosial', false)
        ->assertSee('Alokasi Belum Lengkap Petugas', false)
        ->assertSee('DSRT Siap Verifikasi', false)
        ->assertSee('Manifest Masuk dari PML', false);
});

test('social_operator dapat membuka form manifest serah terima', function (): void {
    $sosial = sosialOperatorUji();

    $this->actingAs($sosial)->get(route('document_manifests.index'))->assertOk();
    $this->actingAs($sosial)->get(route('document_manifests.create'))->assertOk();
});

test('social_operator ditolak pada modul admin dan audit', function (): void {
    $sosial = sosialOperatorUji();

    $this->actingAs($sosial)->get('/admin/users')->assertForbidden();
    $this->actingAs($sosial)->get('/admin/roles')->assertForbidden();
    $this->actingAs($sosial)->get('/audit-logs')->assertForbidden();
});
