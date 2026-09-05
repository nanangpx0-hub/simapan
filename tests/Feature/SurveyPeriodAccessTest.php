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

function adminPeriodeUji(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    return $admin;
}

function tipePeriodeUji(string $code = 'TIPE'): SurveyType
{
    return SurveyType::create([
        'code' => $code, 'name' => 'Tipe Uji', 'is_active' => true,
    ]);
}

function periodeDraftUji(User $admin, SurveyType $type, string $code = 'PRD-01', string $status = 'DRAFT'): SurveyPeriod
{
    return SurveyPeriod::create([
        'code' => $code,
        'survey_type_id' => $type->getKey(),
        'name' => 'Periode Uji',
        'period_type' => 'SEMESTER',
        'period_number' => 1,
        'year' => 2099,
        'start_date' => '2099-01-01',
        'end_date' => '2099-06-30',
        'status' => $status,
        'created_by' => $admin->getKey(),
    ]);
}

test('guest diarahkan ke login pada periode survei', function (): void {
    $this->get('/master/periode-survei')->assertRedirect('/login');
    $this->get('/master/periode-survei/create')->assertRedirect('/login');
});

test('user tanpa view mendapat 403 pada periode survei', function (): void {
    $user = User::factory()->create();
    $user->assignRole('field_officer');

    $this->actingAs($user)->get('/master/periode-survei')->assertForbidden();
});

test('user tanpa manage mendapat 403 pada tulis periode survei', function (): void {
    $admin = adminPeriodeUji();
    $type = tipePeriodeUji();
    $period = periodeDraftUji($admin, $type);

    $user = User::factory()->create();
    $user->givePermissionTo('master.survey_period.view');

    $this->actingAs($user)->get('/master/periode-survei/create')->assertForbidden();
    $this->actingAs($user)->post(route('master.survey_periods.store'), [
        'code' => 'BARU-01', 'survey_type_id' => $type->id, 'name' => 'Baru Uji',
        'period_type' => 'SEMESTER', 'period_number' => 2, 'year' => 2099,
        'start_date' => '2099-07-01', 'end_date' => '2099-12-31',
    ])->assertForbidden();
    $this->actingAs($user)->get(route('master.survey_periods.edit', $period))->assertForbidden();
    $this->actingAs($user)->put(route('master.survey_periods.update', $period), [
        'name' => 'Diubah Uji', 'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
    ])->assertForbidden();
    $this->actingAs($user)->post(route('master.survey_periods.activate', $period))->assertForbidden();
});

test('user dengan view dapat membuka daftar dan detail periode', function (): void {
    $admin = adminPeriodeUji();
    $period = periodeDraftUji($admin, tipePeriodeUji(), 'LIHAT-01');

    $user = User::factory()->create();
    $user->givePermissionTo('master.survey_period.view');

    $this->actingAs($user)->get('/master/periode-survei')->assertOk()->assertSee('LIHAT-01');
    $this->actingAs($user)->get(route('master.survey_periods.show', $period))->assertOk();
});

test('tidak ada endpoint delete periode survei', function (): void {
    $admin = adminPeriodeUji();
    $period = periodeDraftUji($admin, tipePeriodeUji(), 'HAPUS-01');

    $this->actingAs($admin)->delete('/master/periode-survei/'.$period->id)->assertStatus(405);
    expect(SurveyPeriod::where('code', 'HAPUS-01')->exists())->toBeTrue();
});
