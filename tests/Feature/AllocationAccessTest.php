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

function adminAlokasiUji(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    return $admin;
}

function periodeAlokasiUji(string $code = 'ALOK-PER-01', string $status = 'DRAFT'): SurveyPeriod
{
    $type = SurveyType::where('code', 'SUSENAS')->firstOrFail();
    $admin = User::factory()->create();

    return SurveyPeriod::create([
        'code' => $code,
        'survey_type_id' => $type->getKey(),
        'name' => 'Periode Alokasi Uji',
        'period_type' => 'SEMESTER',
        'period_number' => 1,
        'year' => 2099,
        'start_date' => '2099-01-01',
        'end_date' => '2099-06-30',
        'status' => $status,
        'created_by' => $admin->getKey(),
    ]);
}

function desaAlokasiUji(): Region
{
    return Region::where('full_code', '9901001001')->firstOrFail();
}

function alokasiUji(User $admin, SurveyPeriod $period, Region $desa, string $nks = 'NKS-UJI-001'): Allocation
{
    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_code' => 'SLS-UJI-01',
        'sub_sls_code' => '0',
        'sls_name' => 'SLS Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
}

test('guest diarahkan ke login pada alokasi', function (): void {
    $this->get('/alokasi')->assertRedirect('/login');
    $this->get('/alokasi/create')->assertRedirect('/login');
});

test('user tanpa allocation.view mendapat 403', function (): void {
    $admin = adminAlokasiUji();
    $alokasi = alokasiUji($admin, periodeAlokasiUji(), desaAlokasiUji());

    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)->get('/alokasi')->assertForbidden();
    $this->actingAs($user)->get(route('allocations.show', $alokasi))->assertForbidden();
});

test('user tanpa allocation.manage mendapat 403 tulis dan status', function (): void {
    $admin = adminAlokasiUji();
    $period = periodeAlokasiUji();
    $desa = desaAlokasiUji();
    $alokasi = alokasiUji($admin, $period, $desa);

    $user = User::factory()->create();
    $user->givePermissionTo('allocation.view');

    $this->actingAs($user)->get('/alokasi/create')->assertForbidden();
    $this->actingAs($user)->post(route('allocations.store'), [
        'survey_period_id' => $period->id,
        'village_region_id' => $desa->id,
        'nks' => 'NKS-UJI-002',
        'sls_name' => 'SLS Uji',
    ])->assertForbidden();
    $this->actingAs($user)->get(route('allocations.edit', $alokasi))->assertForbidden();
    $this->actingAs($user)->put(route('allocations.update', $alokasi), [
        'sls_name' => 'Ubah Uji',
    ])->assertForbidden();
    $this->actingAs($user)->post(route('allocations.activate', $alokasi))->assertForbidden();
});

test('user tanpa allocation.assign mendapat 403 penugasan', function (): void {
    $admin = adminAlokasiUji();
    $alokasi = alokasiUji($admin, periodeAlokasiUji(), desaAlokasiUji());

    $user = User::factory()->create();
    $user->givePermissionTo('allocation.view', 'allocation.manage');

    $this->actingAs($user)->post(route('allocations.assignments.store', $alokasi), [
        'officer_id' => 1,
        'assignment_role' => 'FIELD_OFFICER',
    ])->assertForbidden();
});

test('administrator dapat akses seluruh halaman alokasi', function (): void {
    $admin = adminAlokasiUji();
    $alokasi = alokasiUji($admin, periodeAlokasiUji(), desaAlokasiUji());

    $this->actingAs($admin)->get('/alokasi')->assertOk();
    $this->actingAs($admin)->get('/alokasi/create')->assertOk();
    $this->actingAs($admin)->get(route('allocations.show', $alokasi))->assertOk();
    $this->actingAs($admin)->get(route('allocations.edit', $alokasi))->assertOk();
    $this->actingAs($admin)->get(route('allocations.assignments.index', $alokasi))->assertOk();
});

test('navigasi alokasi mengikuti permission view', function (): void {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertDontSee('Alokasi Kegiatan', false);

    $allowed = User::factory()->create();
    $allowed->givePermissionTo('dashboard.view', 'allocation.view');

    $this->actingAs($allowed)->get('/dashboard')->assertOk()->assertSee('Alokasi Kegiatan', false);
    $this->actingAs($viewer)->get('/alokasi')->assertForbidden();
});
