<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrator');
});

test('super admin dapat membuat role baru dengan permission', function (): void {
    $response = $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), [
        'name' => 'demo_role',
        'permissions' => ['master.region.view', 'allocation.view'],
    ]);

    $response->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'demo_role')->first();
    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('master.region.view'))->toBeTrue()
        ->and($role->hasPermissionTo('allocation.view'))->toBeTrue();
});

test('administrator tidak dapat membuat role (403)', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.roles.store'), ['name' => 'sneaky_role'])
        ->assertForbidden();

    expect(Role::query()->where('name', 'sneaky_role')->exists())->toBeFalse();
});

test('super admin dapat mengubah permission role custom', function (): void {
    $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), [
        'name' => 'demo_role',
        'permissions' => ['master.region.view'],
    ]);

    $role = Role::query()->where('name', 'demo_role')->firstOrFail();

    $this->actingAs($this->superAdmin)
        ->put(route('admin.roles.update', $role), [
            'name' => 'demo_role',
            'permissions' => ['allocation.view', 'dsrt.view'],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role->refresh();
    expect($role->hasPermissionTo('allocation.view'))->toBeTrue()
        ->and($role->hasPermissionTo('master.region.view'))->toBeFalse();
});

test('super admin dapat rename role custom', function (): void {
    $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), ['name' => 'demo_role']);

    $role = Role::query()->where('name', 'demo_role')->firstOrFail();

    $this->actingAs($this->superAdmin)
        ->put(route('admin.roles.update', $role), ['name' => 'renamed_demo'])
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::query()->where('name', 'renamed_demo')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'demo_role')->exists())->toBeFalse();
});

test('role katalog tidak dapat direname atau dihapus (termasuk super_admin)', function (): void {
    foreach (['super_admin', 'administrator', 'viewer'] as $slug) {
        $role = Role::query()->where('name', $slug)->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $role), ['name' => 'renamed_demo', 'permissions' => []])
            ->assertRedirect(route('admin.roles.index'));

        expect(Role::query()->whereKey($role->getKey())->firstOrFail()->name)->toBe($slug);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors();

        expect(Role::query()->whereKey($role->getKey())->exists())->toBeTrue();
    }
});

test('role yang masih dipakai user tidak dapat dihapus', function (): void {
    $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), ['name' => 'demo_role']);
    $role = Role::query()->where('name', 'demo_role')->firstOrFail();

    $user = User::factory()->create();
    $user->assignRole('demo_role');

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertSessionHasErrors();

    expect(Role::query()->whereKey($role->getKey())->exists())->toBeTrue();
});

test('super admin dapat menghapus role custom tanpa user', function (): void {
    $this->actingAs($this->superAdmin)->post(route('admin.roles.store'), ['name' => 'demo_role']);
    $role = Role::query()->where('name', 'demo_role')->firstOrFail();

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::query()->whereKey($role->getKey())->exists())->toBeFalse();
});

test('hanya super admin yang dapat menetapkan role super_admin pada user baru', function (): void {
    $payload = [
        'name' => 'Calon Super',
        'email' => 'calon.super@example.test',
        'password' => 'Password-Rahasia-2026',
        'password_confirmation' => 'Password-Rahasia-2026',
        'roles' => ['super_admin'],
    ];

    $this->actingAs($this->admin)->post(route('admin.users.store'), $payload)->assertForbidden();
    expect(User::query()->where('email', 'calon.super@example.test')->exists())->toBeFalse();

    $this->actingAs($this->superAdmin)->post(route('admin.users.store'), $payload)->assertRedirect(route('admin.users.index'));

    $created = User::query()->where('email', 'calon.super@example.test')->firstOrFail();
    expect($created->hasRole('super_admin'))->toBeTrue();
});

test('administrator tidak dapat memberi role super_admin lewat update user', function (): void {
    $target = User::factory()->create();
    $target->assignRole('viewer');

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => ['super_admin'],
        ])
        ->assertForbidden();

    expect($target->refresh()->hasRole('super_admin'))->toBeFalse();
});

test('super admin dapat menetapkan role super_admin lewat update user', function (): void {
    $target = User::factory()->create();
    $target->assignRole('viewer');

    $this->actingAs($this->superAdmin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => ['super_admin'],
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($target->refresh()->hasRole('super_admin'))->toBeTrue();
});

test('akun super admin tidak dapat diubah oleh administrator', function (): void {
    $target = User::factory()->create();
    $target->assignRole('super_admin');

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $target), [
            'name' => 'Nama Diganti',
            'email' => $target->email,
            'roles' => ['viewer'],
        ])
        ->assertForbidden();

    expect($target->refresh()->name)->not->toBe('Nama Diganti');
});

test('akun super admin tidak dapat dihapus oleh administrator', function (): void {
    $target = User::factory()->create();
    $target->assignRole('super_admin');

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $target))
        ->assertForbidden();

    expect(User::query()->whereKey($target->getKey())->exists())->toBeTrue();
});

test('tidak ada user yang dapat mengubah role dirinya sendiri', function (): void {
    $this->actingAs($this->superAdmin)
        ->put(route('admin.users.update', $this->superAdmin), [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'roles' => ['viewer'],
        ])
        ->assertForbidden();

    expect($this->superAdmin->refresh()->hasRole('super_admin'))->toBeTrue();
});

test('permission role tidak dapat diubah oleh non-super admin', function (): void {
    $role = Role::query()->where('name', 'viewer')->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('admin.roles.update', $role), [
            'name' => 'viewer',
            'permissions' => ['admin.user.manage'],
        ])
        ->assertForbidden();

    expect($role->refresh()->hasPermissionTo('admin.user.manage'))->toBeFalse();
});
