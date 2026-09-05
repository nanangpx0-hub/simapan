<?php

declare(strict_types=1);

use App\Livewire\Master\OfficerTable;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();
});

test('guest diarahkan ke login pada export petugas', function (): void {
    $this->get(route('master.officers.export'))->assertRedirect(route('login'));
});

test('user tanpa view mendapat 403 pada export petugas', function (): void {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)->get(route('master.officers.export'))->assertForbidden();
});

test('user dengan view dapat export xlsx petugas', function (): void {
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo('master.officer.view');

    $this->actingAs($user)->get(route('master.officers.export'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('import petugas butuh permission manage', function (): void {
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo('master.officer.view');

    $this->actingAs($user)->post(route('master.officers.import'), [])->assertForbidden();
});

test('import petugas menolak file bukan excel', function (): void {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('administrator');

    $this->actingAs($admin)->post(route('master.officers.import'), [
        'file' => UploadedFile::fake()->create('data.txt', 10),
    ])->assertSessionHasErrors('file');
});

test('audit-logs tanpa route export', function (): void {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('administrator');

    $this->actingAs($admin)->get('/audit-logs/export')->assertNotFound();
});

test('officer table perPage hanya allowlist', function (): void {
    $user = User::factory()->create(['is_active' => true]);
    $user->givePermissionTo('master.officer.view');

    $this->actingAs($user);

    Livewire::test(OfficerTable::class)
        ->set('perPage', 999)
        ->assertSet('perPage', 10)
        ->set('perPage', 25)
        ->assertSet('perPage', 25)
        ->set('search', 'PTG')
        ->call('resetFilters')
        ->assertSet('search', '');
});
