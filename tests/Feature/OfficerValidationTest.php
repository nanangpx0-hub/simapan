<?php

declare(strict_types=1);

use App\Models\Officer;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkUnitSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        WorkUnitSeeder::class,
    ]);
});

function adminPetugasUji(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    return $admin;
}

function unitPetugasUji(string $code = 'UNITU'): WorkUnit
{
    return WorkUnit::where('code', $code)->first() ?? WorkUnit::create([
        'code' => $code, 'name' => 'Unit Uji', 'is_active' => true,
    ]);
}

function payloadPetugasUji(WorkUnit $unit, array $overrides = []): array
{
    return array_merge([
        'code' => 'VALID-01',
        'name' => 'Petugas Valid Uji',
        'work_unit_id' => $unit->getKey(),
    ], $overrides);
}

test('code valid diterima', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();

    foreach (['AB12', 'a-b_c', 'X', 'OFF-001'] as $code) {
        $this->actingAs($admin)->post(
            route('master.officers.store'),
            payloadPetugasUji($unit, ['code' => $code])
        )->assertRedirect(route('master.officers.index'));
    }

    expect(Officer::count())->toBe(4);
});

test('code regex tidak valid ditolak', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();

    foreach (['kecil spasi', 'ADA SPASI', 'simbol!', 'titik.koma', ''] as $code) {
        $this->actingAs($admin)->post(
            route('master.officers.store'),
            payloadPetugasUji($unit, ['code' => $code])
        )->assertSessionHasErrors('code');
    }

    expect(Officer::count())->toBe(0);
});

test('code duplikat ditolak', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();
    Officer::create(['code' => 'GANDA-01', 'name' => 'Ganda Uji', 'work_unit_id' => $unit->getKey()]);

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'GANDA-01'])
    )->assertSessionHasErrors('code');

    expect(Officer::where('code', 'GANDA-01')->count())->toBe(1);
});

test('code immutable saat update', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();
    $officer = Officer::create(['code' => 'TETAP-01', 'name' => 'Tetap Uji', 'work_unit_id' => $unit->getKey()]);

    $this->actingAs($admin)->put(route('master.officers.update', $officer), [
        'code' => 'GANTI-01', 'name' => 'Tetap Uji', 'work_unit_id' => $unit->getKey(),
    ])->assertSessionHasErrors('code');

    expect($officer->refresh()->code)->toBe('TETAP-01');
});

test('name wajib dan max length', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'NAMA-01', 'name' => ''])
    )->assertSessionHasErrors('name');

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'NAMA-02', 'name' => str_repeat('a', 151)])
    )->assertSessionHasErrors('name');

    expect(Officer::count())->toBe(0);
});

test('work unit nonaktif ditolak', function (): void {
    $admin = adminPetugasUji();
    $unit = WorkUnit::create(['code' => 'UNITM', 'name' => 'Unit Mati Uji', 'is_active' => false]);

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'UNITM-01'])
    )->assertSessionHasErrors('work_unit_id');

    expect(Officer::where('code', 'UNITM-01')->exists())->toBeFalse();
});

test('status invalid ditolak', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'STAT-01', 'status' => 'CUTI'])
    )->assertSessionHasErrors('status');

    expect(Officer::where('code', 'STAT-01')->exists())->toBeFalse();
});

test('active_until sebelum active_from ditolak', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, [
            'code' => 'TGL-01', 'active_from' => '2099-06-01', 'active_until' => '2099-01-01',
        ])
    )->assertSessionHasErrors('active_until');

    expect(Officer::where('code', 'TGL-01')->exists())->toBeFalse();
});

test('normalized_name dibentuk server side dan ditolak dari request', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'NORM-01', 'normalized_name' => 'hack'])
    )->assertSessionHasErrors('normalized_name');

    expect(Officer::where('code', 'NORM-01')->exists())->toBeFalse();

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'NORM-02', 'name' => '  Petugas   Contoh A  '])
    )->assertRedirect(route('master.officers.index'));

    expect(Officer::where('code', 'NORM-02')->firstOrFail()->normalized_name)->toBe('petugas contoh a');
});

test('user dapat ditautkan ke officer', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();
    $user = User::factory()->create();

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'TAUT-01', 'user_id' => $user->id])
    )->assertRedirect(route('master.officers.index'));

    expect(Officer::where('code', 'TAUT-01')->firstOrFail()->user_id)->toBe((int) $user->id);
});

test('satu user tidak dapat ditautkan ke dua officer', function (): void {
    $admin = adminPetugasUji();
    $unit = unitPetugasUji();
    $user = User::factory()->create();
    Officer::create([
        'code' => 'TAUT-A', 'name' => 'Taut A Uji',
        'work_unit_id' => $unit->getKey(), 'user_id' => $user->id,
    ]);

    $this->actingAs($admin)->post(
        route('master.officers.store'),
        payloadPetugasUji($unit, ['code' => 'TAUT-B', 'user_id' => $user->id])
    )->assertSessionHasErrors('user_id');

    expect(Officer::where('code', 'TAUT-B')->exists())->toBeFalse();
});

test('nilai phone email tidak bocor ke daftar umum', function (): void {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('master.officer.view');
    $unit = unitPetugasUji();
    Officer::create([
        'code' => 'RAHASIA-01', 'name' => 'Rahasia Uji', 'work_unit_id' => $unit->getKey(),
        'phone' => '0800000001', 'email' => 'rahasia-uji@simapan.test',
    ]);

    $response = $this->actingAs($viewer)->get('/master/petugas');

    $response->assertOk();
    $response->assertDontSee('0800000001', false);
    $response->assertDontSee('rahasia-uji@simapan.test', false);
});
