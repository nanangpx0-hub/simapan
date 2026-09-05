<?php

declare(strict_types=1);

use App\Models\AuditLog;
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

test('aktivitas mutasi super admin tercatat di audit trail', function (): void {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.roles.store'), ['name' => 'demo_role']);

    $log = AuditLog::query()
        ->where('action', 'super_admin_request')
        ->where('user_id', $this->superAdmin->getKey())
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();

    $metadata = $log->metadata;
    expect($metadata['method'])->toBe('POST')
        ->and($metadata['route'])->toBe('admin.roles.store')
        ->and($metadata['path'])->not->toBe('');
});

test('manajemen role oleh super admin menghasilkan audit khusus', function (): void {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.roles.store'), ['name' => 'demo_role', 'permissions' => ['allocation.view']]);

    $role = Role::query()->where('name', 'demo_role')->firstOrFail();

    expect(AuditLog::query()->where('action', 'role_created')
        ->where('auditable_id', $role->getKey())->exists())->toBeTrue();

    $this->actingAs($this->superAdmin)
        ->put(route('admin.roles.update', $role), ['name' => 'demo_role', 'permissions' => ['dsrt.view']]);

    expect(AuditLog::query()->where('action', 'role_updated')
        ->where('auditable_id', $role->getKey())->exists())->toBeTrue();

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.roles.destroy', $role));

    expect(AuditLog::query()->where('action', 'role_deleted')->exists())->toBeTrue();
});

test('aktivitas mutasi non-super admin tidak dicatat sebagai super_admin_request', function (): void {
    $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'User Baru',
        'email' => 'user.baru@example.test',
        'password' => 'Password-Rahasia-2026',
        'password_confirmation' => 'Password-Rahasia-2026',
        'roles' => ['viewer'],
    ]);

    expect(AuditLog::query()->where('action', 'super_admin_request')->exists())->toBeFalse();
});

test('audit tidak menyimpan password atau token', function (): void {
    $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
        'name' => 'User Baru',
        'email' => 'rahasia.super@example.test',
        'password' => 'Password-Rahasia-2026',
        'password_confirmation' => 'Password-Rahasia-2026',
        'roles' => ['viewer'],
    ]);

    $all = AuditLog::query()->get();
    foreach ($all as $log) {
        $serialized = json_encode([$log->old_values, $log->new_values, $log->metadata]);
        expect($serialized)->not->toContain('Password-Rahasia-2026');
    }
});
