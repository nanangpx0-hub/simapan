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

test('guest diarahkan ke login pada petugas', function (): void {
    $this->get('/master/petugas')->assertRedirect('/login');
    $this->get('/master/petugas/create')->assertRedirect('/login');
});

test('user tanpa view mendapat 403 pada daftar dan detail', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create([
        'code' => 'LIH-01', 'name' => 'Lihat Uji', 'work_unit_id' => $unit->getKey(),
    ]);

    $this->actingAs($user)->get('/master/petugas')->assertForbidden();
    $this->actingAs($user)->get(route('master.officers.show', $officer))->assertForbidden();
});

test('user tanpa manage mendapat 403 pada tulis petugas', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.officer.view');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create([
        'code' => 'JAGA-01', 'name' => 'Jaga Uji', 'work_unit_id' => $unit->getKey(),
    ]);

    $this->actingAs($user)->get('/master/petugas/create')->assertForbidden();
    $this->actingAs($user)->post(route('master.officers.store'), [
        'code' => 'BARU-01', 'name' => 'Baru Uji', 'work_unit_id' => $unit->id,
    ])->assertForbidden();
    $this->actingAs($user)->get(route('master.officers.edit', $officer))->assertForbidden();
    $this->actingAs($user)->put(route('master.officers.update', $officer), [
        'name' => 'Diubah Uji', 'work_unit_id' => $unit->id,
    ])->assertForbidden();
});

test('user dengan view dapat melihat daftar dan detail', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.officer.view');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create([
        'code' => 'TAMP-01', 'name' => 'Tampil Uji', 'work_unit_id' => $unit->getKey(),
    ]);

    $this->actingAs($user)->get('/master/petugas')->assertOk()->assertSee('TAMP-01');
    $this->actingAs($user)->get(route('master.officers.show', $officer))->assertOk();
});

test('administrator dapat create update dan nonaktifkan petugas', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

    $this->actingAs($admin)->post(route('master.officers.store'), [
        'code' => 'KEL-01', 'name' => 'Kelola Uji', 'work_unit_id' => $unit->id,
    ])->assertRedirect(route('master.officers.index'));

    $officer = Officer::where('code', 'KEL-01')->firstOrFail();

    $this->actingAs($admin)->put(route('master.officers.update', $officer), [
        'name' => 'Kelola Ubah Uji', 'work_unit_id' => $unit->id, 'status' => 'INACTIVE',
    ])->assertRedirect(route('master.officers.index'));

    expect($officer->refresh()->status)->toBe('INACTIVE');
});

test('menu master petugas mengikuti permission view', function (): void {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertDontSee('Master Petugas', false);

    $allowed = User::factory()->create();
    $allowed->givePermissionTo('dashboard.view', 'master.officer.view');

    $this->actingAs($allowed)->get('/dashboard')->assertOk()->assertSee('Master Petugas', false);
    $this->actingAs($viewer)->get('/master/petugas')->assertForbidden();
});
