<?php

declare(strict_types=1);

use App\Actions\Master\AssignOfficer;
use App\Models\Allocation;
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

function alokasiStatusUji(User $admin, string $nks, string $status = 'DRAFT'): Allocation
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'Status Uji',
        'status' => $status,
        'created_by' => $admin->getKey(),
    ]);
}

function tugaskanEmpatUji(User $admin, Allocation $alokasi): void
{
    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();
    $action = app(AssignOfficer::class);

    $pairs = [
        'FIELD_OFFICER' => [$sosial, 'ST-PCL'],
        'FIELD_SUPERVISOR' => [$sosial, 'ST-PML'],
        'PROCESSING_OFFICER' => [$olah, 'ST-OLAH'],
        'PROCESSING_SUPERVISOR' => [$olah, 'ST-WAS'],
    ];

    foreach ($pairs as $role => [$unit, $code]) {
        $officer = Officer::create([
            'code' => $code, 'name' => 'Status Uji '.$code, 'work_unit_id' => $unit->getKey(),
        ]);

        $action->handle($alokasi, $officer, $role, null, $admin);
    }
}

test('semua transisi valid berhasil', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $period->update(['status' => 'ACTIVE']);

    $alokasi = alokasiStatusUji($admin, 'NKS-TR-001');
    tugaskanEmpatUji($admin, $alokasi);

    $this->actingAs($admin)->post(route('allocations.activate', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('ACTIVE');

    $this->actingAs($admin)->post(route('allocations.suspend', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('SUSPENDED');

    $this->actingAs($admin)->post(route('allocations.resume', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('ACTIVE');

    $this->actingAs($admin)->post(route('allocations.complete', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('COMPLETED');

    $this->actingAs($admin)->post(route('allocations.archive', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('ARCHIVED');
});

test('semua transisi invalid ditolak 422', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $draft = alokasiStatusUji($admin, 'NKS-IV-001');
    $this->actingAs($admin)->post(route('allocations.suspend', $draft))->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('allocations.complete', $draft))->assertSessionHasErrors('status');

    $suspended = alokasiStatusUji($admin, 'NKS-IV-002', 'SUSPENDED');
    $this->actingAs($admin)->post(route('allocations.activate', $suspended))->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('allocations.complete', $suspended))->assertSessionHasErrors('status');

    $completed = alokasiStatusUji($admin, 'NKS-IV-003', 'COMPLETED');
    $this->actingAs($admin)->post(route('allocations.activate', $completed))->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('allocations.suspend', $completed))->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('allocations.resume', $completed))->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('allocations.complete', $completed))->assertSessionHasErrors('status');

    $archived = alokasiStatusUji($admin, 'NKS-IV-004', 'ARCHIVED');
    foreach (['activate', 'suspend', 'resume', 'complete', 'archive'] as $action) {
        $this->actingAs($admin)->post(route('allocations.'.$action, $archived))->assertSessionHasErrors('status');
    }

    expect($draft->refresh()->status)->toBe('DRAFT');
    expect($archived->refresh()->status)->toBe('ARCHIVED');
});

test('draft tidak dapat active jika period tidak active', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiStatusUji($admin, 'NKS-PER-001');
    tugaskanEmpatUji($admin, $alokasi);

    $this->actingAs($admin)->post(route('allocations.activate', $alokasi))->assertSessionHasErrors('status');

    expect($alokasi->refresh()->status)->toBe('DRAFT');
});

test('draft tidak dapat active tanpa empat assignment lengkap', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $period->update(['status' => 'ACTIVE']);

    $alokasi = alokasiStatusUji($admin, 'NKS-LENG-001');

    $this->actingAs($admin)->post(route('allocations.activate', $alokasi))->assertSessionHasErrors('status');

    expect($alokasi->refresh()->status)->toBe('DRAFT');
});

test('suspended dapat resume dan archive', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiStatusUji($admin, 'NKS-SUS-001', 'SUSPENDED');

    $this->actingAs($admin)->post(route('allocations.resume', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('ACTIVE');

    $this->actingAs($admin)->post(route('allocations.suspend', $alokasi))->assertRedirect();

    $this->actingAs($admin)->post(route('allocations.archive', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('ARCHIVED');
});

test('completed hanya dapat archive dan archived final', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $alokasi = alokasiStatusUji($admin, 'NKS-CMP-001', 'COMPLETED');

    $this->actingAs($admin)->post(route('allocations.archive', $alokasi))->assertRedirect();
    expect($alokasi->refresh()->status)->toBe('ARCHIVED');

    $this->actingAs($admin)->post(route('allocations.activate', $alokasi))->assertSessionHasErrors('status');
    expect($alokasi->refresh()->status)->toBe('ARCHIVED');
});
