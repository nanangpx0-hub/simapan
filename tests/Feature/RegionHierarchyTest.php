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

function buatProvinsiUji(string $code = 'PROV'): Region
{
    return Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => $code,
        'full_code' => $code, 'name' => 'Provinsi Uji', 'is_active' => true,
    ]);
}

function adminWilayahUji(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    return $admin;
}

test('dapat membuat provinsi root tanpa parent', function (): void {
    $admin = adminWilayahUji();

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'PROVINSI', 'code' => 'ROOTW', 'name' => 'Root Uji',
    ]);

    $response->assertRedirect(route('master.wilayah.index'));
    expect(Region::where('full_code', 'ROOTW')->firstOrFail()->parent_id)->toBeNull();
});

test('tidak dapat membuat provinsi dengan parent', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji();

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'PROVINSI', 'code' => 'SALAH', 'name' => 'Salah Uji', 'parent_id' => $prov->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect(Region::where('code', 'SALAH')->exists())->toBeFalse();
});

test('dapat membuat kab kota dengan parent provinsi aktif', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji();

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KAB_KOTA', 'code' => 'KAB1', 'name' => 'Kab Uji', 'parent_id' => $prov->id,
    ]);

    $response->assertRedirect(route('master.wilayah.index'));
    expect(Region::where('full_code', 'PROVKAB1')->exists())->toBeTrue();
});

test('tidak dapat membuat kab kota dengan parent kecamatan atau desa', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji();
    $kab = Region::create([
        'parent_id' => $prov->id, 'level' => 'KAB_KOTA', 'code' => 'KABX',
        'full_code' => 'PROVKABX', 'name' => 'Kab X Uji', 'is_active' => true,
    ]);
    $kec = Region::create([
        'parent_id' => $kab->id, 'level' => 'KECAMATAN', 'code' => 'KECX',
        'full_code' => 'PROVKABXKECX', 'name' => 'Kec X Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KAB_KOTA', 'code' => 'KABZ', 'name' => 'Kab Z Uji', 'parent_id' => $kec->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect(Region::where('code', 'KABZ')->exists())->toBeFalse();
});

test('dapat membuat kecamatan dan desa berantai', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji();
    $kab = Region::create([
        'parent_id' => $prov->id, 'level' => 'KAB_KOTA', 'code' => 'KABR',
        'full_code' => 'PROVKABR', 'name' => 'Kab R Uji', 'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KECAMATAN', 'code' => 'KECR', 'name' => 'Kec R Uji', 'parent_id' => $kab->id,
    ])->assertRedirect(route('master.wilayah.index'));

    $kec = Region::where('full_code', 'PROVKABRKECR')->firstOrFail();

    $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'DESA_KELURAHAN_NAGARI', 'code' => 'DESR', 'name' => 'Desa R Uji', 'parent_id' => $kec->id,
    ])->assertRedirect(route('master.wilayah.index'));

    expect(Region::where('full_code', 'PROVKABRKECRDESR')->exists())->toBeTrue();
});

test('parent salah level ditolak 422', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji();

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'DESA_KELURAHAN_NAGARI', 'code' => 'DESX', 'name' => 'Desa X Uji', 'parent_id' => $prov->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect(Region::where('code', 'DESX')->exists())->toBeFalse();
});

test('parent nonaktif ditolak 422', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji();
    $prov->update(['is_active' => false]);

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'KAB_KOTA', 'code' => 'KABM', 'name' => 'Kab M Uji', 'parent_id' => $prov->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect(Region::where('code', 'KABM')->exists())->toBeFalse();
});

test('self parent ditolak 422', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji();

    $response = $this->actingAs($admin)->put(route('master.wilayah.update', $prov), [
        'name' => 'Provinsi Uji', 'parent_id' => $prov->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
});

test('cycle ditolak 422', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji('SIKLUS');
    $kab = Region::create([
        'parent_id' => $prov->id, 'level' => 'KAB_KOTA', 'code' => 'KABS',
        'full_code' => 'SIKLUSKABS', 'name' => 'Kab S Uji', 'is_active' => true,
    ]);
    $kec = Region::create([
        'parent_id' => $kab->id, 'level' => 'KECAMATAN', 'code' => 'KECS',
        'full_code' => 'SIKLUSKABSKECS', 'name' => 'Kec S Uji', 'is_active' => true,
    ]);

    // Pindahkan kab ke bawah kecamatannya sendiri (level salah + cycle).
    $response = $this->actingAs($admin)->put(route('master.wilayah.update', $kab), [
        'name' => 'Kab S Uji', 'parent_id' => $kec->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect($kab->refresh()->parent_id)->toBe($prov->id);
});

test('cycle murni antar level valid ditolak', function (): void {
    $admin = adminWilayahUji();
    $provA = buatProvinsiUji('PROVA');
    $provB = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'PROVB',
        'full_code' => 'PROVB', 'name' => 'Prov B Uji', 'is_active' => true,
    ]);
    $kab = Region::create([
        'parent_id' => $provA->id, 'level' => 'KAB_KOTA', 'code' => 'KABC',
        'full_code' => 'PROVAKABC', 'name' => 'Kab C Uji', 'is_active' => true,
    ]);

    // Simulasi rantai: pindahkan kab ke provB (valid), lalu coba kembalikan via descendant.
    $this->actingAs($admin)->put(route('master.wilayah.update', $kab), [
        'name' => 'Kab C Uji', 'parent_id' => $provB->id,
    ])->assertRedirect(route('master.wilayah.index'));

    $kec = Region::create([
        'parent_id' => $kab->id, 'level' => 'KECAMATAN', 'code' => 'KECC',
        'full_code' => 'PROVBKABCKECC', 'name' => 'Kec C Uji', 'is_active' => true,
    ]);

    // Coba jadikan kec sebagai parent dari kab (level salah, juga descendant).
    $this->actingAs($admin)->put(route('master.wilayah.update', $kab), [
        'name' => 'Kab C Uji', 'parent_id' => $kec->id,
    ])->assertSessionHasErrors('parent_id');
});

test('child aktif mencegah parent dinonaktifkan', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji('INDUKW');
    $kab = Region::create([
        'parent_id' => $prov->id, 'level' => 'KAB_KOTA', 'code' => 'KABW',
        'full_code' => 'INDUKWKABW', 'name' => 'Kab W Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put(route('master.wilayah.update', $prov), [
        'name' => 'Provinsi Uji', 'parent_id' => null, 'is_active' => false,
    ]);

    // Provinsi tanpa parent: parent_id null memicu aturan wajib parent? Tidak:
    // level PROVINSI membolehkan null; yang diuji adalah is_active.
    $response->assertSessionHasErrors('is_active');
    expect($prov->refresh()->is_active)->toBeTrue();

    // Setelah child dinonaktifkan, parent boleh dinonaktifkan.
    $kab->update(['is_active' => false]);

    $this->actingAs($admin)->put(route('master.wilayah.update', $prov), [
        'name' => 'Provinsi Uji', 'is_active' => false,
    ])->assertRedirect(route('master.wilayah.index'));

    expect($prov->refresh()->is_active)->toBeFalse();
});

test('child dapat dipindahkan ke parent valid bila tidak punya child', function (): void {
    $admin = adminWilayahUji();
    $provA = buatProvinsiUji('PINDAHA');
    $provB = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'PINDAHB',
        'full_code' => 'PINDAHB', 'name' => 'Pindah B Uji', 'is_active' => true,
    ]);
    $kab = Region::create([
        'parent_id' => $provA->id, 'level' => 'KAB_KOTA', 'code' => 'KABP',
        'full_code' => 'PINDAHAKABP', 'name' => 'Kab P Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put(route('master.wilayah.update', $kab), [
        'name' => 'Kab P Uji', 'parent_id' => $provB->id,
    ]);

    $response->assertRedirect(route('master.wilayah.index'));
    expect($kab->refresh()->full_code)->toBe('PINDAHBKABP');
});

test('region ber-child tidak dapat dipindahkan parent', function (): void {
    $admin = adminWilayahUji();
    $provA = buatProvinsiUji('TETAPA');
    $provB = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'TETAPB',
        'full_code' => 'TETAPB', 'name' => 'Tetap B Uji', 'is_active' => true,
    ]);
    $kab = Region::create([
        'parent_id' => $provA->id, 'level' => 'KAB_KOTA', 'code' => 'KABT',
        'full_code' => 'TETAPAKABT', 'name' => 'Kab T Uji', 'is_active' => true,
    ]);
    Region::create([
        'parent_id' => $kab->id, 'level' => 'KECAMATAN', 'code' => 'KECT',
        'full_code' => 'TETAPAKABTKECT', 'name' => 'Kec T Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put(route('master.wilayah.update', $kab), [
        'name' => 'Kab T Uji', 'parent_id' => $provB->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect($kab->refresh()->parent_id)->toBe($provA->id);
    expect($kab->refresh()->full_code)->toBe('TETAPAKABT');
});

test('tidak ada endpoint delete wilayah', function (): void {
    $admin = adminWilayahUji();
    $prov = buatProvinsiUji('HAPUSW');

    $this->actingAs($admin)->delete('/master/wilayah/'.$prov->id)->assertStatus(405);
    expect(Region::where('code', 'HAPUSW')->exists())->toBeTrue();
});
