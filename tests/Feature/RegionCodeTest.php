<?php

declare(strict_types=1);

use App\Models\Region;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function adminKodeUji(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    return $admin;
}

test('code duplikat pada path parent level sama ditolak', function (): void {
    $admin = adminKodeUji();
    $prov = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'DUP',
        'full_code' => 'DUP', 'name' => 'Dup Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KAB_KOTA', 'code' => 'SAMA', 'name' => 'Sama Uji', 'parent_id' => $prov->id,
    ]);
    $response->assertRedirect(route('master.wilayah.index'));

    $duplikat = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KAB_KOTA', 'code' => 'SAMA', 'name' => 'Sama Lain Uji', 'parent_id' => $prov->id,
    ]);

    $duplikat->assertSessionHasErrors('code');
    expect(Region::where('full_code', 'DUPSAMA')->count())->toBe(1);
});

test('full_code duplikat antar cabang ditolak', function (): void {
    $admin = adminKodeUji();
    $provA = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'AB',
        'full_code' => 'AB', 'name' => 'Prov AB Uji', 'is_active' => true,
    ]);
    $provB = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'A',
        'full_code' => 'A', 'name' => 'Prov A Uji', 'is_active' => true,
    ]);
    Region::create([
        'parent_id' => $provA->id, 'level' => 'KAB_KOTA', 'code' => 'C',
        'full_code' => 'ABC', 'name' => 'Kab C Uji', 'is_active' => true,
    ]);

    // KAB_KOTA code BC di bawah A menghasilkan full ABC yang sudah ada.
    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KAB_KOTA', 'code' => 'BC', 'name' => 'Kab BC Uji', 'parent_id' => $provB->id,
    ]);

    $response->assertSessionHasErrors('code');
    expect(Region::where('full_code', 'ABC')->count())->toBe(1);
});

test('code valid diterima', function (): void {
    $admin = adminKodeUji();
    $prov = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'PVL',
        'full_code' => 'PVL', 'name' => 'Prov Uji', 'is_active' => true,
    ]);

    foreach (['AB12', 'a-b_c', 'X', '0123456789ABCDEFGHIJ0123456789AB'] as $code) {
        $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
            'level' => 'KAB_KOTA', 'code' => $code, 'name' => 'Kab Uji', 'parent_id' => $prov->id,
        ]);

        $response->assertRedirect(route('master.wilayah.index'));
    }

    expect(Region::where('parent_id', $prov->id)->count())->toBe(4);
});

test('code tidak valid ditolak', function (): void {
    $admin = adminKodeUji();
    $prov = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'VALID',
        'full_code' => 'VALID', 'name' => 'Valid Uji', 'is_active' => true,
    ]);

    foreach (['ADA SPASI', 'simbol!', 'titik.koma', '', '0123456789ABCDEFGHIJ0123456789ABC'] as $code) {
        $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
            'level' => 'KAB_KOTA', 'code' => $code, 'name' => 'Kab Uji', 'parent_id' => $prov->id,
        ]);

        $response->assertSessionHasErrors('code');
    }
});

test('full_code dibentuk server side dan diabaikan dari request', function (): void {
    $admin = adminKodeUji();

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'PROVINSI', 'code' => 'SRV', 'name' => 'Srv Uji', 'full_code' => 'HACK',
    ]);

    $response->assertSessionHasErrors('full_code');
    expect(Region::where('code', 'SRV')->exists())->toBeFalse();

    $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'PROVINSI', 'code' => 'SRV', 'name' => 'Srv Uji',
    ])->assertRedirect(route('master.wilayah.index'));

    $region = Region::where('code', 'SRV')->firstOrFail();
    expect($region->full_code)->toBe('SRV');

    $this->actingAs($admin)->put(route('master.wilayah.update', $region), [
        'name' => 'Srv Ubah Uji', 'full_code' => 'HACK2',
    ])->assertSessionHasErrors('full_code');

    expect($region->refresh()->full_code)->toBe('SRV');
});

test('code dan level tidak dapat diubah setelah create', function (): void {
    $admin = adminKodeUji();
    $region = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'KEKAL',
        'full_code' => 'KEKAL', 'name' => 'Kekal Uji', 'is_active' => true,
    ]);

    $this->actingAs($admin)->put(route('master.wilayah.update', $region), [
        'code' => 'GANTI', 'name' => 'Kekal Uji',
    ])->assertSessionHasErrors('code');

    $this->actingAs($admin)->put(route('master.wilayah.update', $region), [
        'level' => 'KAB_KOTA', 'name' => 'Kekal Uji',
    ])->assertSessionHasErrors('level');

    $region->refresh();
    expect($region->code)->toBe('KEKAL');
    expect($region->level)->toBe('PROVINSI');
    expect($region->full_code)->toBe('KEKAL');
});

test('parent update valid memperbarui full_code tanpa child', function (): void {
    $admin = adminKodeUji();
    $provA = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'FULFA',
        'full_code' => 'FULFA', 'name' => 'Fulf A Uji', 'is_active' => true,
    ]);
    $provB = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'FULFB',
        'full_code' => 'FULFB', 'name' => 'Fulf B Uji', 'is_active' => true,
    ]);
    $kab = Region::create([
        'parent_id' => $provA->id, 'level' => 'KAB_KOTA', 'code' => 'KABF',
        'full_code' => 'FULFAKABF', 'name' => 'Kab F Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put(route('master.wilayah.update', $kab), [
        'name' => 'Kab F Uji', 'parent_id' => $provB->id,
    ]);

    $response->assertRedirect(route('master.wilayah.index'));
    expect($kab->refresh()->full_code)->toBe('FULFBKABF');
    expect($kab->refresh()->parent_id)->toBe($provB->id);
});

test('update tanpa mengubah parent mempertahankan full_code', function (): void {
    $admin = adminKodeUji();
    $prov = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'TETAPF',
        'full_code' => 'TETAPF', 'name' => 'Tetap F Uji', 'is_active' => true,
    ]);
    $kab = Region::create([
        'parent_id' => $prov->id, 'level' => 'KAB_KOTA', 'code' => 'KABT',
        'full_code' => 'TETAPFKABT', 'name' => 'Kab T Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put(route('master.wilayah.update', $kab), [
        'name' => 'Kab T Ubah Uji', 'parent_id' => $prov->id,
    ]);

    $response->assertRedirect(route('master.wilayah.index'));
    expect($kab->refresh()->full_code)->toBe('TETAPFKABT');
    expect($kab->refresh()->name)->toBe('Kab T Ubah Uji');
});

test('bentrok full_code antar cabang dicegah unique constraint', function (): void {
    $admin = adminKodeUji();
    $provA = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'AA',
        'full_code' => 'AA', 'name' => 'Prov AA Uji', 'is_active' => true,
    ]);
    Region::create([
        'parent_id' => $provA->id, 'level' => 'KAB_KOTA', 'code' => 'BB',
        'full_code' => 'AABB', 'name' => 'Kab BB Uji', 'is_active' => true,
    ]);
    $provB = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'AAB',
        'full_code' => 'AAB', 'name' => 'Prov AAB Uji', 'is_active' => true,
    ]);

    // AAB + B = AABB yang sudah dipakai → validasi menolak sebelum DB.
    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KAB_KOTA', 'code' => 'B', 'name' => 'Kab B Uji', 'parent_id' => $provB->id,
    ]);

    $response->assertSessionHasErrors('code');
    expect(Region::where('full_code', 'AABB')->count())->toBe(1);
});
