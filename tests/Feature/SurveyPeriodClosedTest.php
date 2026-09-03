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

test('closed mengisi closed_by dan closed_at', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'CLSD', 'name' => 'Clsd Uji', 'is_active' => true]);
    $period = SurveyPeriod::create([
        'code' => 'CLSD-01', 'survey_type_id' => $type->getKey(), 'name' => 'Clsd Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    expect($period->closed_by)->toBeNull();
    expect($period->closed_at)->toBeNull();

    $this->actingAs($admin)->post(route('master.survey_periods.activate', $period))->assertRedirect();

    expect($period->refresh()->closed_by)->toBeNull();
    expect($period->refresh()->closed_at)->toBeNull();

    $this->actingAs($admin)->post(route('master.survey_periods.close', $period))->assertRedirect();

    $period->refresh();
    expect($period->status)->toBe('CLOSED');
    expect((int) $period->closed_by)->toBe((int) $admin->getKey());
    expect($period->closed_at)->not->toBeNull();
    expect($period->closer->id)->toBe($admin->id);
});

test('archived tidak dapat diubah atau diaktifkan kembali', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'ARSP', 'name' => 'Arsp Uji', 'is_active' => true]);
    $period = SurveyPeriod::create([
        'code' => 'ARSP-01', 'survey_type_id' => $type->getKey(), 'name' => 'Arsp Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('master.survey_periods.archive', $period))->assertRedirect();
    expect($period->refresh()->status)->toBe('ARCHIVED');

    $this->actingAs($admin)->put(route('master.survey_periods.update', $period), [
        'name' => 'Coba Ubah Uji',
        'start_date' => '2099-01-01',
        'end_date' => '2099-06-30',
    ])->assertSessionHasErrors('status');

    $this->actingAs($admin)->post(route('master.survey_periods.activate', $period))
        ->assertSessionHasErrors('status');

    expect($period->refresh()->name)->toBe('Arsp Uji');
    expect($period->refresh()->status)->toBe('ARCHIVED');
});
