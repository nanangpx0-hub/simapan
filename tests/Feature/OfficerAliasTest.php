<?php

declare(strict_types=1);

use App\Models\Officer;
use App\Models\OfficerAlias;
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

function petugasAliasUji(string $code = 'ALS-01', string $name = 'Alias Induk Uji'): Officer
{
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

    return Officer::create([
        'code' => $code, 'name' => $name, 'work_unit_id' => $unit->getKey(),
    ]);
}

test('admin dapat tambah alias pada petugas', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $officer = petugasAliasUji();

    $response = $this->actingAs($admin)->post(route('master.officers.aliases.store', $officer), [
        'alias_name' => '  P.   Alias Uji  ',
    ]);

    $response->assertRedirect(route('master.officers.aliases.index', $officer));

    $alias = OfficerAlias::where('officer_id', $officer->id)->firstOrFail();
    expect($alias->normalized_alias)->toBe('p. alias uji');
    expect((int) $alias->created_by)->toBe((int) $admin->getKey());
});

test('normalized_alias dari request ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $officer = petugasAliasUji('ALS-02');

    $this->actingAs($admin)->post(route('master.officers.aliases.store', $officer), [
        'alias_name' => 'Alias Hack Uji',
        'normalized_alias' => 'hack',
    ])->assertSessionHasErrors('normalized_alias');

    expect(OfficerAlias::where('officer_id', $officer->id)->count())->toBe(0);
});

test('alias duplikat setelah normalisasi pada petugas sama ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $officer = petugasAliasUji('ALS-03');
    $officer->aliases()->create(['alias_name' => 'Alias Ganda Uji']);

    $this->actingAs($admin)->post(route('master.officers.aliases.store', $officer), [
        'alias_name' => '  ALIAS   ganda uji ',
    ])->assertSessionHasErrors('alias_name');

    expect(OfficerAlias::where('officer_id', $officer->id)->count())->toBe(1);
});

test('alias sama boleh dipakai officer berbeda dengan warning', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $officerA = petugasAliasUji('ALS-A', 'Induk A Uji');
    $officerB = petugasAliasUji('ALS-B', 'Induk B Uji');
    $officerA->aliases()->create(['alias_name' => 'Nama Kembar Uji']);

    $response = $this->actingAs($admin)->post(route('master.officers.aliases.store', $officerB), [
        'alias_name' => 'nama kembar uji',
    ]);

    $response->assertRedirect(route('master.officers.aliases.index', $officerB));
    $response->assertSessionHas('warning');
    expect(OfficerAlias::where('officer_id', $officerB->id)->count())->toBe(1);
});

test('alias sama dengan nama utama officer yang sama ditolak', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $officer = petugasAliasUji('ALS-04', 'Nama Utama Uji');

    $this->actingAs($admin)->post(route('master.officers.aliases.store', $officer), [
        'alias_name' => '  NAMA   utama uji ',
    ])->assertSessionHasErrors('alias_name');

    expect(OfficerAlias::where('officer_id', $officer->id)->count())->toBe(0);
});

test('admin dapat edit alias tanpa duplikasi', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $officer = petugasAliasUji('ALS-05');
    $alias = $officer->aliases()->create(['alias_name' => 'Alias Lama Uji']);
    $officer->aliases()->create(['alias_name' => 'Alias Tetap Uji']);

    $this->actingAs($admin)->put(route('master.officers.aliases.update', [$officer, $alias]), [
        'alias_name' => 'Alias Baru Uji',
    ])->assertRedirect(route('master.officers.aliases.index', $officer));

    expect($alias->refresh()->normalized_alias)->toBe('alias baru uji');

    $this->actingAs($admin)->put(route('master.officers.aliases.update', [$officer, $alias]), [
        'alias_name' => 'alias tetap uji',
    ])->assertSessionHasErrors('alias_name');
});

test('user tanpa manage tidak dapat tambah atau edit alias', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('master.officer.view');
    $officer = petugasAliasUji('ALS-06');
    $alias = $officer->aliases()->create(['alias_name' => 'Alias Jaga Uji']);

    $this->actingAs($user)->get(route('master.officers.aliases.create', $officer))->assertForbidden();
    $this->actingAs($user)->post(route('master.officers.aliases.store', $officer), [
        'alias_name' => 'Alias Nakal Uji',
    ])->assertForbidden();
    $this->actingAs($user)->get(route('master.officers.aliases.edit', [$officer, $alias]))->assertForbidden();
    $this->actingAs($user)->put(route('master.officers.aliases.update', [$officer, $alias]), [
        'alias_name' => 'Alias Nakal Uji',
    ])->assertForbidden();
});

test('tidak ada endpoint delete alias', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $officer = petugasAliasUji('ALS-07');
    $alias = $officer->aliases()->create(['alias_name' => 'Alias Hapus Uji']);

    $this->actingAs($admin)->delete('/master/petugas/'.$officer->id.'/alias/'.$alias->id)->assertStatus(405);
    expect(OfficerAlias::where('id', $alias->id)->exists())->toBeTrue();
});

test('pencarian menemukan via code nama dan alias', function (): void {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('master.officer.view');
    $officer = petugasAliasUji('CARI-01', 'Nama Cari Induk Uji');
    $officer->aliases()->create(['alias_name' => 'Julukan Cari Uji']);

    $this->actingAs($viewer)->get('/master/petugas?q=CARI-01')->assertOk()->assertSee('CARI-01');
    $this->actingAs($viewer)->get('/master/petugas?q=Nama+Cari')->assertOk()->assertSee('CARI-01');
    $this->actingAs($viewer)->get('/master/petugas?q=julukan+cari')->assertOk()->assertSee('CARI-01');
});
