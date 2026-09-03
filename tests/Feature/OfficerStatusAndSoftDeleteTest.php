<?php

declare(strict_types=1);

use App\Models\Officer;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkUnitSeeder;
use Illuminate\Database\QueryException;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        WorkUnitSeeder::class,
    ]);
});

test('admin dapat mengubah active menjadi inactive', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create(['code' => 'STT-01', 'name' => 'Status Uji', 'work_unit_id' => $unit->getKey()]);

    $this->actingAs($admin)->put(route('master.officers.update', $officer), [
        'name' => 'Status Uji', 'work_unit_id' => $unit->getKey(), 'status' => 'INACTIVE',
    ])->assertRedirect(route('master.officers.index'));

    expect($officer->refresh()->status)->toBe('INACTIVE');
});

test('petugas inactive tidak muncul pada scope active', function (): void {
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    Officer::create([
        'code' => 'AKT-01', 'name' => 'Akt Uji', 'work_unit_id' => $unit->getKey(), 'status' => 'ACTIVE',
    ]);
    Officer::create([
        'code' => 'NON-01', 'name' => 'Non Uji', 'work_unit_id' => $unit->getKey(), 'status' => 'INACTIVE',
    ]);

    expect(Officer::active()->pluck('code')->all())->toBe(['AKT-01']);
});

test('petugas inactive tetap terlihat bila filter mengizinkan', function (): void {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('master.officer.view');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    Officer::create([
        'code' => 'FLT-01', 'name' => 'Filter Uji', 'work_unit_id' => $unit->getKey(), 'status' => 'INACTIVE',
    ]);

    $response = $this->actingAs($viewer)->get('/master/petugas?status=INACTIVE');

    $response->assertOk();
    $response->assertSee('FLT-01');
});

test('tidak ada endpoint delete petugas', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create(['code' => 'HPS-01', 'name' => 'Hapus Uji', 'work_unit_id' => $unit->getKey()]);

    $this->actingAs($admin)->delete('/master/petugas/'.$officer->id)->assertStatus(405);
    expect(Officer::where('code', 'HPS-01')->exists())->toBeTrue();
});

test('soft delete model tanpa route dan code tidak dapat dipakai ulang', function (): void {
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create(['code' => 'SFT-01', 'name' => 'Soft Uji', 'work_unit_id' => $unit->getKey()]);

    $officer->delete();

    $this->assertSoftDeleted('officers', ['code' => 'SFT-01']);
    expect(Officer::where('code', 'SFT-01')->exists())->toBeFalse();

    try {
        Officer::create(['code' => 'SFT-01', 'name' => 'Soft Lain Uji', 'work_unit_id' => $unit->getKey()]);
        $this->fail('Kode soft-deleted seharusnya tetap dilindungi unique global.');
    } catch (QueryException) {
        expect(true)->toBeTrue();
    }
});
