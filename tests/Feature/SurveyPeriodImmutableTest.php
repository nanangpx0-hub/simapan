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

test('field immutable tidak dapat diubah setelah create', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $typeA = SurveyType::create(['code' => 'IMTA', 'name' => 'Imt A Uji', 'is_active' => true]);
    $typeB = SurveyType::create(['code' => 'IMTB', 'name' => 'Imt B Uji', 'is_active' => true]);
    $period = SurveyPeriod::create([
        'code' => 'IMT-01', 'survey_type_id' => $typeA->getKey(), 'name' => 'Imt Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $base = ['name' => 'Imt Uji', 'start_date' => '2099-01-01', 'end_date' => '2099-06-30'];

    $this->actingAs($admin)->put(
        route('master.survey_periods.update', $period),
        array_merge($base, ['code' => 'GANTI-01'])
    )->assertSessionHasErrors('code');

    $this->actingAs($admin)->put(
        route('master.survey_periods.update', $period),
        array_merge($base, ['survey_type_id' => $typeB->getKey()])
    )->assertSessionHasErrors('survey_type_id');

    $this->actingAs($admin)->put(
        route('master.survey_periods.update', $period),
        array_merge($base, ['period_type' => 'TRIWULAN'])
    )->assertSessionHasErrors('period_type');

    $this->actingAs($admin)->put(
        route('master.survey_periods.update', $period),
        array_merge($base, ['period_number' => 2])
    )->assertSessionHasErrors('period_number');

    $this->actingAs($admin)->put(
        route('master.survey_periods.update', $period),
        array_merge($base, ['year' => 2100])
    )->assertSessionHasErrors('year');

    $period->refresh();
    expect($period->code)->toBe('IMT-01');
    expect($period->survey_type_id)->toBe((int) $typeA->getKey());
    expect($period->period_type)->toBe('SEMESTER');
    expect($period->period_number)->toBe(1);
    expect($period->year)->toBe(2099);
});

test('name dan tanggal dapat diubah saat draft', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'DRFT', 'name' => 'Drft Uji', 'is_active' => true]);
    $period = SurveyPeriod::create([
        'code' => 'DRFT-01', 'survey_type_id' => $type->getKey(), 'name' => 'Nama Lama Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $response = $this->actingAs($admin)->put(route('master.survey_periods.update', $period), [
        'name' => 'Nama Baru Uji',
        'start_date' => '2099-02-01',
        'end_date' => '2099-05-31',
    ]);

    $response->assertRedirect(route('master.survey_periods.index'));

    $period->refresh();
    expect($period->name)->toBe('Nama Baru Uji');
    expect($period->start_date->format('Y-m-d'))->toBe('2099-02-01');
    expect($period->end_date->format('Y-m-d'))->toBe('2099-05-31');
});

test('periode non-draft tidak dapat diubah via update', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'NDRF', 'name' => 'Ndrf Uji', 'is_active' => true]);

    foreach (['ACTIVE', 'CLOSED', 'ARCHIVED'] as $status) {
        $period = SurveyPeriod::create([
            'code' => 'NDRF-'.$status, 'survey_type_id' => $type->getKey(), 'name' => 'Nama Uji',
            'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
            'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
            'status' => $status, 'created_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)->put(route('master.survey_periods.update', $period), [
            'name' => 'Coba Ubah Uji',
            'start_date' => '2099-01-01',
            'end_date' => '2099-06-30',
        ])->assertSessionHasErrors('status');

        expect($period->refresh()->name)->toBe('Nama Uji');
    }
});
