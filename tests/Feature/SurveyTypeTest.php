<?php

declare(strict_types=1);

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

test('guest diarahkan ke login pada jenis survei', function (): void {
    $this->get('/master/jenis-survei')->assertRedirect('/login');
    $this->get('/master/jenis-survei/create')->assertRedirect('/login');
});

test('user tanpa view mendapat 403 pada jenis survei', function (): void {
    $user = User::factory()->create();
    $user->assignRole('field_officer');

    $this->actingAs($user)->get('/master/jenis-survei')->assertForbidden();
});

test('user dengan view dapat membuka daftar jenis survei', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.survey_type.view');
    SurveyType::create(['code' => 'CONTOH', 'name' => 'Contoh Uji', 'is_active' => true]);

    $response = $this->actingAs($user)->get('/master/jenis-survei');

    $response->assertOk();
    $response->assertSee('CONTOH');
});

test('user tanpa manage tidak dapat create atau update jenis survei', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.survey_type.view');
    $type = SurveyType::create(['code' => 'JAGA', 'name' => 'Jaga Uji', 'is_active' => true]);

    $this->actingAs($user)->post(route('master.jenis-survei.store'), [
        'code' => 'BARU',
        'name' => 'Baru Uji',
    ])->assertForbidden();

    $this->actingAs($user)->put(route('master.jenis-survei.update', $type), [
        'name' => 'Diubah Uji',
    ])->assertForbidden();
});

test('administrator dapat create survey type', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $response = $this->actingAs($admin)->post(route('master.jenis-survei.store'), [
        'code' => 'UJI_CB',
        'name' => 'Uji Coba',
        'description' => 'Deskripsi dummy uji',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('master.jenis-survei.index'));
    expect(SurveyType::where('code', 'UJI_CB')->exists())->toBeTrue();
});

test('code duplikat ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    SurveyType::create(['code' => 'GANDA', 'name' => 'Ganda Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->post(route('master.jenis-survei.store'), [
        'code' => 'GANDA',
        'name' => 'Ganda Lain Uji',
    ]);

    $response->assertSessionHasErrors('code');
    expect(SurveyType::where('code', 'GANDA')->count())->toBe(1);
});

test('code tidak valid ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    foreach (['kecil', 'ADA SPASI', 'simbol!', ''] as $code) {
        $response = $this->actingAs($admin)->post(route('master.jenis-survei.store'), [
            'code' => $code,
            'name' => 'Nama Uji',
        ]);

        $response->assertSessionHasErrors('code');
    }

    expect(SurveyType::count())->toBe(0);
});

test('code tidak dapat diubah setelah create', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'TETAP', 'name' => 'Tetap Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->put(route('master.jenis-survei.update', $type), [
        'code' => 'BERUBAH',
        'name' => 'Tetap Uji',
    ]);

    $response->assertSessionHasErrors('code');
    expect($type->refresh()->code)->toBe('TETAP');
});

test('nonaktifkan survey type oleh user berwenang', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'NONAKTIF', 'name' => 'Nonaktif Uji', 'is_active' => true]);

    $response = $this->actingAs($admin)->put(route('master.jenis-survei.update', $type), [
        'name' => 'Nonaktif Uji',
        'is_active' => false,
    ]);

    $response->assertRedirect(route('master.jenis-survei.index'));
    expect($type->refresh()->is_active)->toBeFalse();
});

test('tidak ada endpoint delete jenis survei', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'HAPUS', 'name' => 'Hapus Uji', 'is_active' => true]);

    $this->actingAs($admin)->delete('/master/jenis-survei/'.$type->id)->assertStatus(405);
    expect(SurveyType::where('code', 'HAPUS')->exists())->toBeTrue();
});
