<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

test('guest diarahkan ke login pada unit kerja', function (): void {
    $this->get('/master/unit-kerja')->assertRedirect('/login');
    $this->get('/master/unit-kerja/create')->assertRedirect('/login');
});

test('user tanpa view mendapat 403 pada unit kerja', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)->get('/master/unit-kerja')->assertForbidden();
});

test('user dengan view dapat membuka daftar unit kerja', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.work_unit.view');
    WorkUnit::create(['code' => 'CONTOH', 'name' => 'Contoh Uji', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/master/unit-kerja');

    $response->assertOk();
    $response->assertSee('CONTOH');
});

test('user tanpa manage tidak dapat create atau update unit kerja', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.work_unit.view');
    $unit = WorkUnit::create(['code' => 'JAGA', 'name' => 'Jaga Uji', 'is_active' => true]);

    $this->actingAs($user)->post(route('master.unit-kerja.store'), [
        'code' => 'BARU',
        'name' => 'Baru Uji',
    ])->assertForbidden();

    $this->actingAs($user)->put(route('master.unit-kerja.update', $unit), [
        'name' => 'Diubah Uji',
    ])->assertForbidden();
});

test('administrator dapat membuat root unit', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->post(route('master.unit-kerja.store'), [
        'code' => 'ROOT_UJI',
        'name' => 'Root Uji',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('master.unit-kerja.index'));

    $unit = WorkUnit::where('code', 'ROOT_UJI')->firstOrFail();
    expect($unit->parent_id)->toBeNull();
});

test('administrator dapat membuat child unit dengan parent valid', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $parent = WorkUnit::create(['code' => 'INDUK', 'name' => 'Induk Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(route('master.unit-kerja.store'), [
        'code' => 'ANAK',
        'name' => 'Anak Uji',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('master.unit-kerja.index'));
    expect(WorkUnit::where('code', 'ANAK')->firstOrFail()->parent_id)->toBe($parent->id);
});

test('code unit duplikat ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    WorkUnit::create(['code' => 'GANDA', 'name' => 'Ganda Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(route('master.unit-kerja.store'), [
        'code' => 'GANDA',
        'name' => 'Ganda Lain Uji',
    ]);

    $response->assertSessionHasErrors('code');
    expect(WorkUnit::where('code', 'GANDA')->count())->toBe(1);
});

test('code unit tidak valid ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    foreach (['kecil', 'ADA SPASI', 'simbol!', ''] as $code) {
        $response = $this->actingAs($admin)->post(route('master.unit-kerja.store'), [
            'code' => $code,
            'name' => 'Nama Uji',
        ]);

        $response->assertSessionHasErrors('code');
    }

    expect(WorkUnit::count())->toBe(0);
});

test('code unit tidak dapat diubah setelah create', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::create(['code' => 'TETAP', 'name' => 'Tetap Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->put(route('master.unit-kerja.update', $unit), [
        'code' => 'BERUBAH',
        'name' => 'Tetap Uji',
    ]);

    $response->assertSessionHasErrors('code');
    expect($unit->refresh()->code)->toBe('TETAP');
});

test('unit tidak dapat menjadi parent dirinya sendiri', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::create(['code' => 'SENDIRI', 'name' => 'Sendiri Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->put(route('master.unit-kerja.update', $unit), [
        'name' => 'Sendiri Uji',
        'parent_id' => $unit->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect($unit->refresh()->parent_id)->toBeNull();
});

test('siklus parent child ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unitA = WorkUnit::create(['code' => 'UNIT_A', 'name' => 'Unit A Uji', 'is_active' => true]);
    $unitB = WorkUnit::create(['code' => 'UNIT_B', 'name' => 'Unit B Uji', 'parent_id' => $unitA->id, 'is_active' => true]);

    $response = $this->actingAs($admin)->put(route('master.unit-kerja.update', $unitA), [
        'name' => 'Unit A Uji',
        'parent_id' => $unitB->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect($unitA->refresh()->parent_id)->toBeNull();
});

test('parent nonaktif tidak dapat dipilih untuk child baru', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $parent = WorkUnit::create(['code' => 'MATI', 'name' => 'Mati Uji', 'is_active' => false]);

    $response = $this->actingAs($admin)->post(route('master.unit-kerja.store'), [
        'code' => 'ANAK_MATI',
        'name' => 'Anak Mati Uji',
        'parent_id' => $parent->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect(WorkUnit::where('code', 'ANAK_MATI')->exists())->toBeFalse();
});

test('unit dengan child tidak dapat dinonaktifkan tanpa penanganan', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $parent = WorkUnit::create(['code' => 'INDUK_A', 'name' => 'Induk A Uji', 'is_active' => true]);
    $child = WorkUnit::create(['code' => 'ANAK_A', 'name' => 'Anak A Uji', 'parent_id' => $parent->id, 'is_active' => true]);

    $response = $this->actingAs($admin)->put(route('master.unit-kerja.update', $parent), [
        'name' => 'Induk A Uji',
        'is_active' => false,
    ]);

    $response->assertSessionHasErrors('is_active');
    expect($parent->refresh()->is_active)->toBeTrue();

    $this->actingAs($admin)->put(route('master.unit-kerja.update', $child), [
        'name' => 'Anak A Uji',
        'parent_id' => null,
    ])->assertRedirect(route('master.unit-kerja.index'));

    $this->actingAs($admin)->put(route('master.unit-kerja.update', $parent), [
        'name' => 'Induk A Uji',
        'is_active' => false,
    ])->assertRedirect(route('master.unit-kerja.index'));

    expect($parent->refresh()->is_active)->toBeFalse();
});

test('tidak ada endpoint delete unit kerja', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::create(['code' => 'HAPUS', 'name' => 'Hapus Uji', 'is_active' => true]);

    $this->actingAs($admin)->delete('/master/unit-kerja/'.$unit->id)->assertStatus(405);
    expect(WorkUnit::where('code', 'HAPUS')->exists())->toBeTrue();
});
