<?php

declare(strict_types=1);

use App\Actions\Master\AssignOfficer;
use App\Models\Allocation;
use App\Models\DsrtSample;
use App\Models\Officer;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;
use Database\Seeders\WorkUnitSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        WorkUnitSeeder::class,
        RegionSeeder::class,
        SurveyPeriodSeeder::class,
    ]);
});

function ownershipActor(string $role, string $officerCode, string $officerName): array
{
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

    $user = User::factory()->create();
    $user->assignRole($role);

    $officer = Officer::create([
        'code' => $officerCode,
        'name' => $officerName,
        'work_unit_id' => $unit->getKey(),
        'user_id' => $user->getKey(),
        'status' => 'ACTIVE',
    ]);

    return [$user->refresh(), $officer->refresh()];
}

function ownershipAllocation(User $admin, string $nks): Allocation
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'SLS Ownership Uji',
        'status' => 'ACTIVE',
        'created_by' => $admin->getKey(),
    ]);
}

function ownershipSetup(): array
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    [$pplA, $offA] = ownershipActor('field_officer', 'OWN-PPL-A', 'PPL A Uji');
    [$pplB, $offB] = ownershipActor('field_officer', 'OWN-PPL-B', 'PPL B Uji');
    [$pmlA, $offPml] = ownershipActor('field_supervisor', 'OWN-PML-A', 'PML A Uji');

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $alokasiA = ownershipAllocation($admin, 'NKS-OWN-A');
    $alokasiB = ownershipAllocation($admin, 'NKS-OWN-B');

    $action = app(AssignOfficer::class);
    $action->handle($alokasiA, $offA, 'FIELD_OFFICER', 'MITRA', $admin);
    $action->handle($alokasiB, $offB, 'FIELD_OFFICER', 'MITRA', $admin);
    $action->handle($alokasiA, $offPml, 'FIELD_SUPERVISOR', 'ORGANIK', $admin);

    $sampleA = DsrtSample::create([
        'allocation_id' => $alokasiA->getKey(),
        'nus' => 'NUS-OWN-A', 'nurt' => 'NURT-OWN-A', 'krt_name' => 'KRT Own A',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
    $sampleB = DsrtSample::create([
        'allocation_id' => $alokasiB->getKey(),
        'nus' => 'NUS-OWN-B', 'nurt' => 'NURT-OWN-B', 'krt_name' => 'KRT Own B',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    return compact('admin', 'pplA', 'pplB', 'pmlA', 'viewer', 'alokasiA', 'alokasiB', 'sampleA', 'sampleB');
}

test('PPL A dapat melihat alokasinya sendiri tetapi 403 untuk alokasi PPL B', function (): void {
    $s = ownershipSetup();

    $this->actingAs($s['pplA'])->get(route('allocations.show', $s['alokasiA']))->assertOk();
    $this->actingAs($s['pplA'])->get(route('allocations.show', $s['alokasiB']))->assertForbidden();

    expect($s['pplA']->can('view', $s['alokasiA']))->toBeTrue();
    expect($s['pplA']->can('view', $s['alokasiB']))->toBeFalse();
});

test('PPL A dapat melihat DSRT alokasinya tetapi 403 untuk DSRT alokasi lain', function (): void {
    $s = ownershipSetup();

    $this->actingAs($s['pplA'])
        ->get(route('allocations.dsrt.index', $s['alokasiA']))->assertOk();
    $this->actingAs($s['pplA'])
        ->get(route('allocations.dsrt.show', [$s['alokasiA'], $s['sampleA']]))->assertOk();

    $this->actingAs($s['pplA'])
        ->get(route('allocations.dsrt.index', $s['alokasiB']))->assertForbidden();
    $this->actingAs($s['pplA'])
        ->get(route('allocations.dsrt.show', [$s['alokasiB'], $s['sampleB']]))->assertForbidden();
});

test('PML A dapat verifikasi NKS yang diawasi tetapi 403 untuk NKS lain', function (): void {
    $s = ownershipSetup();

    $this->actingAs($s['pmlA'])
        ->post(route('allocations.dsrt.verify', [$s['alokasiA'], $s['sampleA']]))
        ->assertRedirect();
    expect($s['sampleA']->refresh()->record_status)->toBe('VERIFIED');

    $this->actingAs($s['pmlA'])
        ->post(route('allocations.dsrt.verify', [$s['alokasiB'], $s['sampleB']]))
        ->assertForbidden();
    expect($s['sampleB']->refresh()->record_status)->toBe('DRAFT');
});

test('viewer read-only seluruh data tetapi mutasi 403', function (): void {
    $s = ownershipSetup();

    $this->actingAs($s['viewer'])->get(route('allocations.show', $s['alokasiA']))->assertOk();
    $this->actingAs($s['viewer'])->get(route('allocations.show', $s['alokasiB']))->assertOk();
    $this->actingAs($s['viewer'])
        ->get(route('allocations.dsrt.show', [$s['alokasiA'], $s['sampleA']]))->assertOk();

    $this->actingAs($s['viewer'])->put(route('allocations.update', $s['alokasiA']), [
        'sls_name' => 'Ubah Viewer',
    ])->assertForbidden();

    $this->actingAs($s['viewer'])->post(route('allocations.dsrt.verify', [$s['alokasiA'], $s['sampleA']]))
        ->assertForbidden();

    $this->actingAs($s['viewer'])->post(route('allocations.assignments.store', $s['alokasiA']), [
        'officer_id' => 1,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertForbidden();
});

test('assign officer menolak akun tertaut dengan role tidak setara', function (): void {
    $s = ownershipSetup();

    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $wrongUser = User::factory()->create();
    $wrongUser->assignRole('processing_officer');

    $wrongOfficer = Officer::create([
        'code' => 'OWN-WRONG',
        'name' => 'Petugas Salah Role',
        'work_unit_id' => $unit->getKey(),
        'user_id' => $wrongUser->getKey(),
        'status' => 'ACTIVE',
    ]);

    expect(fn () => app(AssignOfficer::class)->handle(
        $s['alokasiB'], $wrongOfficer, 'FIELD_OFFICER', 'MITRA', $s['admin']
    ))->toThrow(ValidationException::class);
});

test('pemetaan spatie role ke assignment role resmi', function (): void {
    expect(User::assignmentRoleFor('field_officer'))->toBe('FIELD_OFFICER');
    expect(User::assignmentRoleFor('field_supervisor'))->toBe('FIELD_SUPERVISOR');
    expect(User::assignmentRoleFor('processing_officer'))->toBe('PROCESSING_OFFICER');
    expect(User::assignmentRoleFor('processing_supervisor'))->toBe('PROCESSING_SUPERVISOR');
    expect(User::assignmentRoleFor('viewer'))->toBeNull();

    expect(User::spatieRoleFor('FIELD_OFFICER'))->toBe('field_officer');
    expect(User::spatieRoleFor('UNKNOWN'))->toBeNull();
});

test('helper officer dan full scope bekerja sesuai role', function (): void {
    $s = ownershipSetup();

    expect($s['pplA']->isOfficer())->toBeTrue();
    expect($s['pplA']->officerId())->toBe((int) Officer::where('code', 'OWN-PPL-A')->firstOrFail()->getKey());
    expect($s['pplA']->hasFullDataScope())->toBeFalse();
    expect($s['viewer']->hasFullDataScope())->toBeTrue();
    expect($s['admin']->hasFullDataScope())->toBeTrue();

    expect($s['pplA']->hasActiveAssignmentRole('FIELD_OFFICER'))->toBeTrue();
    expect($s['pplA']->hasActiveAssignmentRole('FIELD_SUPERVISOR'))->toBeFalse();
});
