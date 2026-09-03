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

function payloadPeriodeUji(SurveyType $type, array $overrides = []): array
{
    return array_merge([
        'code' => 'VALID-01',
        'survey_type_id' => $type->getKey(),
        'name' => 'Periode Valid Uji',
        'period_type' => 'SEMESTER',
        'period_number' => 1,
        'year' => 2099,
        'start_date' => '2099-01-01',
        'end_date' => '2099-06-30',
    ], $overrides);
}

test('admin dapat membuat periode draft valid', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'BUAT', 'name' => 'Buat Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, ['code' => 'BUAT-01'])
    );

    $response->assertRedirect(route('master.survey_periods.index'));

    $period = SurveyPeriod::where('code', 'BUAT-01')->firstOrFail();
    expect($period->status)->toBe('DRAFT');
    expect($period->created_by)->toBe((int) $admin->getKey());
});

test('kode duplikat ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'DUPL', 'name' => 'Dupl Uji', 'is_active' => true]);
    SurveyPeriod::create([
        'code' => 'DUPL-01', 'survey_type_id' => $type->getKey(), 'name' => 'Ada Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, ['code' => 'DUPL-01'])
    );

    $response->assertSessionHasErrors('code');
    expect(SurveyPeriod::where('code', 'DUPL-01')->count())->toBe(1);
});

test('kode tidak valid ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'REGX', 'name' => 'Regx Uji', 'is_active' => true]);

    foreach (['kecil', 'ADA SPASI', 'simbol!', ''] as $code) {
        $this->actingAs($admin)->post(
            route('master.survey_periods.store'),
            payloadPeriodeUji($type, ['code' => $code])
        )->assertSessionHasErrors('code');
    }

    expect(SurveyPeriod::count())->toBe(0);
});

test('survey type nonaktif ditolak saat create', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'MATI', 'name' => 'Mati Uji', 'is_active' => false]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, ['code' => 'MATI-01'])
    );

    $response->assertSessionHasErrors('survey_type_id');
    expect(SurveyPeriod::where('code', 'MATI-01')->exists())->toBeFalse();
});

test('period type invalid ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'PTYP', 'name' => 'Ptyp Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, ['code' => 'PTYP-01', 'period_type' => 'BULANAN'])
    );

    $response->assertSessionHasErrors('period_type');
    expect(SurveyPeriod::where('code', 'PTYP-01')->exists())->toBeFalse();
});

test('nomor semester salah ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'SMST', 'name' => 'Smst Uji', 'is_active' => true]);

    foreach ([0, 3, null] as $number) {
        $this->actingAs($admin)->post(
            route('master.survey_periods.store'),
            payloadPeriodeUji($type, ['code' => 'SMST-'.$number, 'period_number' => $number])
        )->assertSessionHasErrors('period_number');
    }
});

test('nomor triwulan salah ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'TRIW', 'name' => 'Triw Uji', 'is_active' => true]);

    foreach ([0, 5, null] as $number) {
        $this->actingAs($admin)->post(
            route('master.survey_periods.store'),
            payloadPeriodeUji($type, [
                'code' => 'TRIW-'.$number, 'period_type' => 'TRIWULAN', 'period_number' => $number,
            ])
        )->assertSessionHasErrors('period_number');
    }
});

test('tahunan dengan nomor ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'THNN', 'name' => 'Thnn Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, [
            'code' => 'THNN-01', 'period_type' => 'TAHUNAN', 'period_number' => 1,
        ])
    );

    $response->assertSessionHasErrors('period_number');
    expect(SurveyPeriod::where('code', 'THNN-01')->exists())->toBeFalse();
});

test('tahunan tanpa nomor diterima', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'THNY', 'name' => 'Thny Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, [
            'code' => 'THNY-01', 'period_type' => 'TAHUNAN', 'period_number' => null,
        ])
    );

    $response->assertRedirect(route('master.survey_periods.index'));
    expect(SurveyPeriod::where('code', 'THNY-01')->firstOrFail()->period_number)->toBeNull();
});

test('year invalid ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'YEAR', 'name' => 'Year Uji', 'is_active' => true]);

    foreach ([1999, 2101] as $year) {
        $this->actingAs($admin)->post(
            route('master.survey_periods.store'),
            payloadPeriodeUji($type, ['code' => 'YEAR-'.$year, 'year' => $year])
        )->assertSessionHasErrors('year');
    }
});

test('end date sebelum start date ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'TGL', 'name' => 'Tgl Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, [
            'code' => 'TGL-01', 'start_date' => '2099-06-30', 'end_date' => '2099-01-01',
        ])
    );

    $response->assertSessionHasErrors('end_date');
    expect(SurveyPeriod::where('code', 'TGL-01')->exists())->toBeFalse();
});

test('status awal active langsung ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'STTS', 'name' => 'Stts Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(
        route('master.survey_periods.store'),
        payloadPeriodeUji($type, ['code' => 'STTS-01', 'status' => 'ACTIVE'])
    );

    $response->assertSessionHasErrors('status');
    expect(SurveyPeriod::where('code', 'STTS-01')->exists())->toBeFalse();
});
