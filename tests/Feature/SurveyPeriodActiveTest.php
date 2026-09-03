<?php

declare(strict_types=1);

use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function slotAktifUji(User $admin, SurveyType $type, string $code): SurveyPeriod
{
    $period = SurveyPeriod::create([
        'code' => $code, 'survey_type_id' => $type->getKey(), 'name' => 'Slot Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
    $period->update(['status' => 'ACTIVE']);

    return $period->refresh();
}

test('dua active pada kombinasi sama ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'UNIK', 'name' => 'Unik Uji', 'is_active' => true]);
    slotAktifUji($admin, $type, 'UNIK-A');

    $kedua = SurveyPeriod::create([
        'code' => 'UNIK-B', 'survey_type_id' => $type->getKey(), 'name' => 'Kedua Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $response = $this->actingAs($admin)->post(route('master.survey_periods.activate', $kedua));

    $response->assertSessionHasErrors('status');
    expect($kedua->refresh()->status)->toBe('DRAFT');
    expect(SurveyPeriod::active()->count())->toBe(1);
});

test('periode sama boleh active bersamaan untuk jenis survei berbeda', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $susenas = SurveyType::create(['code' => 'SSNA', 'name' => 'Ssna Uji', 'is_active' => true]);
    $seruti = SurveyType::create(['code' => 'SRTB', 'name' => 'Srtb Uji', 'is_active' => true]);

    $periodeA = SurveyPeriod::create([
        'code' => 'SSNA-01', 'survey_type_id' => $susenas->getKey(), 'name' => 'A Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
    $periodeB = SurveyPeriod::create([
        'code' => 'SRTB-01', 'survey_type_id' => $seruti->getKey(), 'name' => 'B Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('master.survey_periods.activate', $periodeA))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $periodeB))->assertRedirect();

    expect($periodeA->refresh()->status)->toBe('ACTIVE');
    expect($periodeB->refresh()->status)->toBe('ACTIVE');
});

test('nomor berbeda boleh active bersamaan', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'NOMR', 'name' => 'Nomr Uji', 'is_active' => true]);

    $satu = SurveyPeriod::create([
        'code' => 'NOMR-1', 'survey_type_id' => $type->getKey(), 'name' => 'Satu Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
    $dua = SurveyPeriod::create([
        'code' => 'NOMR-2', 'survey_type_id' => $type->getKey(), 'name' => 'Dua Uji',
        'period_type' => 'SEMESTER', 'period_number' => 2, 'year' => 2099,
        'start_date' => '2099-07-01', 'end_date' => '2099-12-31',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('master.survey_periods.activate', $satu))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $dua))->assertRedirect();

    expect($satu->refresh()->status)->toBe('ACTIVE');
    expect($dua->refresh()->status)->toBe('ACTIVE');
});
