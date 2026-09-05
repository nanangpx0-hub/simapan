<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\Officer;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\User;
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

function alokasiAksesUji(User $admin, string $nks = 'NKS-AKS-001'): Allocation
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'Akses Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
}

test('authorization halaman penugasan mengikuti permission', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiAksesUji($admin);

    $denied = User::factory()->create();
    $denied->assignRole('processing_officer');

    $this->actingAs($denied)->get(route('allocations.assignments.index', $alokasi))->assertForbidden();

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get(route('allocations.assignments.index', $alokasi))->assertOk();
});

test('user tanpa allocation.assign mendapat 403 assign dan unassign', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiAksesUji($admin);
    $officer = Officer::where('code', 'OFF-001')->firstOrFail();

    $user = User::factory()->create();
    $user->givePermissionTo('allocation.view', 'allocation.manage');

    $this->actingAs($user)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => $officer->id,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertForbidden();

    $assignment = Assignment::create([
        'allocation_id' => $alokasi->getKey(),
        'officer_id' => $officer->getKey(),
        'assignment_role' => 'FIELD_OFFICER',
        'is_active' => true,
        'started_at' => now(),
        'assigned_by' => $admin->getKey(),
    ]);

    $this->actingAs($user)->post(route('allocations.assignments.unassign', [$alokasi, $assignment]))->assertForbidden();
});

test('route mismatch assignment menghasilkan 404 aman', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasiA = alokasiAksesUji($admin, 'NKS-AKS-002');
    $alokasiB = alokasiAksesUji($admin, 'NKS-AKS-003');
    $officer = Officer::where('code', 'OFF-001')->firstOrFail();

    $assignment = Assignment::create([
        'allocation_id' => $alokasiA->getKey(),
        'officer_id' => $officer->getKey(),
        'assignment_role' => 'FIELD_OFFICER',
        'is_active' => true,
        'started_at' => now(),
        'assigned_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('allocations.assignments.unassign', [$alokasiB, $assignment]))->assertNotFound();
    expect($assignment->refresh()->is_active)->toBeTrue();
});
