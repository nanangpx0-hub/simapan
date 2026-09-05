<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\DsrtSample;
use App\Models\Officer;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\AllocationSeeder;
use Database\Seeders\DocumentMasterSeeder;
use Database\Seeders\OfficerSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;
use Database\Seeders\WorkUnitSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        WorkUnitSeeder::class,
        RegionSeeder::class,
        OfficerSeeder::class,
        SurveyPeriodSeeder::class,
        AllocationSeeder::class,
        DocumentMasterSeeder::class,
    ]);
});

function pimpinanUji(): User
{
    $user = User::factory()->create();
    $user->assignRole('viewer');

    return $user;
}

test('viewer melihat executive monitoring dashboard', function (): void {
    $this->actingAs(pimpinanUji())->get('/dashboard')->assertOk()
        ->assertSee('Executive Monitoring Dashboard', false)
        ->assertSee('Progres Lapangan', false)
        ->assertSee('Verifikasi DSRT', false)
        ->assertSee('Alur Dokumen', false)
        ->assertSee('Deadline Periode', false)
        ->assertSee('Di Lapangan', false)
        ->assertSee('SUSENAS', false)
        ->assertSee('SERUTI', false);
});

test('viewer dapat membaca audit trail', function (): void {
    $log = AuditLog::create([
        'event_uuid' => (string) Str::uuid(),
        'action' => 'created',
        'auditable_type' => Officer::class,
        'auditable_id' => 1,
    ]);

    $this->actingAs(pimpinanUji())->get('/audit-logs')->assertOk()
        ->assertSee('Pintas pimpinan', false)
        ->assertSee('Perubahan status alokasi', false);
    $this->actingAs(pimpinanUji())->get(route('audit_logs.show', $log))->assertOk();
});

test('viewer dapat membuka seluruh list dan detail', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-VIEW-01');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-VIEW-01', 'nurt' => 'NURT-VIEW-01', 'krt_name' => 'KRT View Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas View Uji');
    $manifest = manifestDraftUji($admin);
    $officer = Officer::create([
        'code' => 'VIEW-01', 'name' => 'Petugas View Uji',
        'work_unit_id' => WorkUnit::where('code', 'SOSIAL')->firstOrFail()->getKey(),
    ]);

    $viewer = pimpinanUji();

    foreach ([
        '/master/jenis-survei',
        '/master/unit-kerja',
        '/master/periode-survei',
        '/master/wilayah',
        '/master/petugas',
        '/alokasi',
        '/dokumen',
        '/dokumen/jenis',
        '/dokumen/lokasi',
        '/manifest',
    ] as $url) {
        $this->actingAs($viewer)->get($url)->assertOk();
    }

    $this->actingAs($viewer)->get(route('master.officers.show', $officer))->assertOk();
    $this->actingAs($viewer)->get(route('allocations.show', $alokasi))->assertOk();
    $this->actingAs($viewer)->get(route('allocations.dsrt.index', $alokasi))->assertOk();
    $this->actingAs($viewer)->get(route('allocations.dsrt.show', [$alokasi, $sample]))->assertOk();
    $this->actingAs($viewer)->get(route('documents.show', $dokumen))->assertOk();
    $this->actingAs($viewer)->get(route('document_manifests.show', $manifest))->assertOk();
});

test('viewer ditolak pada seluruh aksi mutasi', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-VIEW-02');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-VIEW-02', 'nurt' => 'NURT-VIEW-02', 'krt_name' => 'KRT View Mutasi',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
    [$manifestSiap] = manifestSiapTerimaUji($admin, 1);
    $transferItem = $manifestSiap->transfer()->firstOrFail()->items()->firstOrFail();
    $viewer = pimpinanUji();

    // Tambah user.
    $this->actingAs($viewer)->post(route('admin.users.store'), [
        'name' => 'Coba', 'email' => 'coba@simapan.test',
        'password' => 'password', 'password_confirmation' => 'password',
        'roles' => ['viewer'],
    ])->assertForbidden();

    // Hapus alokasi: tanpa endpoint delete (405).
    $this->actingAs($viewer)->delete(route('allocations.show', $alokasi))->assertStatus(405);

    // Assign petugas.
    $this->actingAs($viewer)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => 1, 'assignment_role' => 'FIELD_OFFICER',
    ])->assertForbidden();

    // Verifikasi DSRT.
    $this->actingAs($viewer)->post(route('allocations.dsrt.verify', [$alokasi, $sample]))->assertForbidden();

    // Terima manifest.
    $this->actingAs($viewer)->put(
        route('document_transfers.update', $manifestSiap),
        payloadTerimaUji($transferItem->getKey(), 1)
    )->assertForbidden();
});

test('viewer dapat mengunduh rekap eksekutif', function (): void {
    $this->actingAs(pimpinanUji())->get(route('allocations.executive-export'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
