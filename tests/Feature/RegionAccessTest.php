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

test('guest diarahkan ke login pada wilayah', function (): void {
    $this->get('/master/wilayah')->assertRedirect('/login');
    $this->get('/master/wilayah/create')->assertRedirect('/login');
});

test('user tanpa view mendapat 403 pada daftar wilayah', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)->get('/master/wilayah')->assertForbidden();
});

test('user tanpa manage mendapat 403 pada tulis wilayah', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.region.view');
    $region = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'JAGA',
        'full_code' => 'JAGA', 'name' => 'Jaga Uji', 'is_active' => true,
    ]);

    $this->actingAs($user)->get('/master/wilayah/create')->assertForbidden();
    $this->actingAs($user)->post(route('master.wilayah.store'), [
        'level' => 'PROVINSI', 'code' => 'BARU', 'name' => 'Baru Uji',
    ])->assertForbidden();
    $this->actingAs($user)->get(route('master.wilayah.edit', $region))->assertForbidden();
    $this->actingAs($user)->put(route('master.wilayah.update', $region), [
        'name' => 'Diubah Uji',
    ])->assertForbidden();
});

test('user dengan view dapat melihat daftar wilayah', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.region.view');
    Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'LIHAT',
        'full_code' => 'LIHAT', 'name' => 'Lihat Uji', 'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get('/master/wilayah');

    $response->assertOk();
    $response->assertSee('LIHAT');
});

test('administrator dapat melihat dan mengelola wilayah', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $this->actingAs($admin)->get('/master/wilayah')->assertOk();
    $this->actingAs($admin)->get('/master/wilayah/create')->assertOk();

    $response = $this->actingAs($admin)->post(route('master.wilayah.store'), [
        'level' => 'PROVINSI', 'code' => 'KELOLA', 'name' => 'Kelola Uji',
    ]);

    $response->assertRedirect(route('master.wilayah.index'));
    expect(Region::where('full_code', 'KELOLA')->exists())->toBeTrue();
});
