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

function periodeTransisiUji(User $admin, SurveyType $type, string $code, string $status): SurveyPeriod
{
    return SurveyPeriod::create([
        'code' => $code, 'survey_type_id' => $type->getKey(), 'name' => 'Transisi Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => $status, 'created_by' => $admin->getKey(),
    ]);
}

test('transisi draft ke active dan archived valid', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'TRNS', 'name' => 'Trns Uji', 'is_active' => true]);

    $aktif = periodeTransisiUji($admin, $type, 'TRNS-ACT', 'DRAFT');
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $aktif))
        ->assertRedirect(route('master.survey_periods.show', $aktif));
    expect($aktif->refresh()->status)->toBe('ACTIVE');

    $arsip = periodeTransisiUji($admin, $type, 'TRNS-ARC', 'DRAFT');
    $this->actingAs($admin)->post(route('master.survey_periods.archive', $arsip))
        ->assertRedirect(route('master.survey_periods.show', $arsip));
    expect($arsip->refresh()->status)->toBe('ARCHIVED');
});

test('transisi active ke closed dan closed ke archived valid', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'TRN2', 'name' => 'Trn2 Uji', 'is_active' => true]);
    $period = periodeTransisiUji($admin, $type, 'TRN2-01', 'DRAFT');

    $this->actingAs($admin)->post(route('master.survey_periods.activate', $period))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.close', $period))->assertRedirect();
    expect($period->refresh()->status)->toBe('CLOSED');

    $this->actingAs($admin)->post(route('master.survey_periods.archive', $period))->assertRedirect();
    expect($period->refresh()->status)->toBe('ARCHIVED');
});

test('semua transisi invalid ditolak 422', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'TRN3', 'name' => 'Trn3 Uji', 'is_active' => true]);

    $draft = periodeTransisiUji($admin, $type, 'TRN3-D', 'DRAFT');
    $this->actingAs($admin)->post(route('master.survey_periods.close', $draft))
        ->assertSessionHasErrors('status');

    $active = periodeTransisiUji($admin, $type, 'TRN3-A', 'DRAFT');
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $active))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $active))
        ->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('master.survey_periods.archive', $active))
        ->assertSessionHasErrors('status');

    $closed = periodeTransisiUji($admin, $type, 'TRN3-C', 'DRAFT');
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $closed))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.close', $closed))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $closed))
        ->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('master.survey_periods.close', $closed))
        ->assertSessionHasErrors('status');

    $archived = periodeTransisiUji($admin, $type, 'TRN3-R', 'DRAFT');
    $this->actingAs($admin)->post(route('master.survey_periods.archive', $archived))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.activate', $archived))
        ->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('master.survey_periods.close', $archived))
        ->assertSessionHasErrors('status');
    $this->actingAs($admin)->post(route('master.survey_periods.archive', $archived))
        ->assertSessionHasErrors('status');
});

test('action status memakai post bukan get', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'TRN4', 'name' => 'Trn4 Uji', 'is_active' => true]);
    $period = periodeTransisiUji($admin, $type, 'TRN4-01', 'DRAFT');

    $this->actingAs($admin)->get('/master/periode-survei/'.$period->id.'/activate')->assertStatus(405);
    expect($period->refresh()->status)->toBe('DRAFT');
});
