<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\AuditLog;
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

function alokasiAuditUji(User $admin, string $nks = 'NKS-AUD-001'): Allocation
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'Audit Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
}

test('create dan update allocation satu audit event', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    $this->actingAs($admin)->post(route('allocations.store'), [
        'survey_period_id' => $period->id,
        'village_region_id' => $desa->id,
        'nks' => 'NKS-AUD-002',
        'sls_name' => 'Audit Buat Uji',
    ])->assertRedirect();

    $alokasi = Allocation::where('nks', 'NKS-AUD-002')->firstOrFail();

    $this->actingAs($admin)->put(route('allocations.update', $alokasi), [
        'sls_name' => 'Audit Ubah Uji',
    ])->assertRedirect();

    $actions = AuditLog::where('auditable_type', Allocation::class)
        ->where('auditable_id', $alokasi->id)->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe(['created', 'updated']);
    expect(AuditLog::where('auditable_type', Allocation::class)->where('auditable_id', $alokasi->id)->where('action', 'updated')->firstOrFail()->new_values['sls_name'])->toBe('Audit Ubah Uji');
});

test('setiap action status satu audit tanpa duplikasi updated', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $period->update(['status' => 'ACTIVE']);

    $alokasi = alokasiAuditUji($admin, 'NKS-AUD-003');

    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();
    $pairs = [
        'FIELD_OFFICER' => $sosial,
        'FIELD_SUPERVISOR' => $sosial,
        'PROCESSING_OFFICER' => $olah,
        'PROCESSING_SUPERVISOR' => $olah,
    ];
    foreach ($pairs as $role => $unit) {
        $officer = Officer::create([
            'code' => 'AUD-'.$role, 'name' => 'Audit Uji '.$role, 'work_unit_id' => $unit->getKey(),
        ]);
        $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
            'officer_id' => $officer->id,
            'assignment_role' => $role,
        ])->assertRedirect();
    }

    $this->actingAs($admin)->post(route('allocations.activate', $alokasi))->assertRedirect();
    $this->actingAs($admin)->post(route('allocations.suspend', $alokasi))->assertRedirect();
    $this->actingAs($admin)->post(route('allocations.resume', $alokasi))->assertRedirect();
    $this->actingAs($admin)->post(route('allocations.complete', $alokasi))->assertRedirect();
    $this->actingAs($admin)->post(route('allocations.archive', $alokasi))->assertRedirect();

    $actions = AuditLog::where('auditable_type', Allocation::class)
        ->where('auditable_id', $alokasi->id)->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe(['created', 'activated', 'suspended', 'resumed', 'completed', 'archived']);
});

test('assign reassign unassign audit tepat satu event', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiAuditUji($admin, 'NKS-AUD-004');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

    $officerA = Officer::create(['code' => 'AUD-A', 'name' => 'Audit A Uji', 'work_unit_id' => $unit->getKey()]);
    $officerB = Officer::create(['code' => 'AUD-B', 'name' => 'Audit B Uji', 'work_unit_id' => $unit->getKey()]);

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $officerA->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $officerB->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertRedirect();

    $rows = AuditLog::where('auditable_type', Assignment::class)->orderBy('id')->pluck('action')->all();

    expect($rows)->toBe(['assigned', 'reassigned']);

    $reassigned = AuditLog::where('auditable_type', Assignment::class)->where('action', 'reassigned')->firstOrFail();
    expect($reassigned->metadata['assignment_role'])->toBe('FIELD_OFFICER');
    expect((int) $reassigned->metadata['previous_officer_id'])->toBe((int) $officerA->id);
    expect((int) $reassigned->metadata['new_officer_id'])->toBe((int) $officerB->id);

    $active = Assignment::where('allocation_id', $alokasi->id)->where('assignment_role', 'FIELD_OFFICER')->active()->firstOrFail();
    $this->actingAs($admin)->post(route('allocations.assignments.unassign', [$alokasi, $active]))->assertRedirect();

    expect(AuditLog::where('auditable_type', Assignment::class)->where('action', 'unassigned')->count())->toBe(1);
});

test('audit metadata bebas pii dan kontak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiAuditUji($admin, 'NKS-AUD-005');

    $raw = json_encode(AuditLog::where('auditable_type', Allocation::class)->where('auditable_id', $alokasi->id)->pluck('new_values')->all());

    expect($raw)->not->toContain('password');
});

test('audit rollback bila operasi bisnis gagal', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiAuditUji($admin, 'NKS-AUD-006');
    $before = AuditLog::where('auditable_type', Allocation::class)->where('auditable_id', $alokasi->id)->count();

    $this->actingAs($admin)->post(route('allocations.activate', $alokasi))->assertSessionHasErrors('status');

    expect(AuditLog::where('auditable_type', Allocation::class)->where('auditable_id', $alokasi->id)->count())->toBe($before);
});
