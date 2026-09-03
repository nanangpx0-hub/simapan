<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\SurveyType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

test('guest diarahkan ke login pada audit logs', function (): void {
    $this->get('/audit-logs')->assertRedirect('/login');
    $this->get('/audit-logs/1')->assertRedirect('/login');
});

test('user tanpa audit.view mendapat 403', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');
    $log = AuditLog::create([
        'event_uuid' => (string) Str::uuid(),
        'action' => 'created',
        'auditable_type' => SurveyType::class,
        'auditable_id' => 1,
    ]);

    $this->actingAs($user)->get('/audit-logs')->assertForbidden();
    $this->actingAs($user)->get(route('audit_logs.show', $log))->assertForbidden();
});

test('administrator dapat membuka daftar dan detail', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $log = AuditLog::create([
        'event_uuid' => (string) Str::uuid(),
        'action' => 'created',
        'auditable_type' => SurveyType::class,
        'auditable_id' => 1,
        'new_values' => ['code' => 'CONTOH'],
    ]);

    $this->actingAs($admin)->get('/audit-logs')->assertOk();
    $this->actingAs($admin)->get(route('audit_logs.show', $log))->assertOk()->assertSee('CONTOH');
});

test('menu audit trail mengikuti permission', function (): void {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertDontSee('Audit Trail', false);

    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Audit Trail', false);
});

test('tidak ada endpoint tulis audit log', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $this->actingAs($admin)->post('/audit-logs')->assertStatus(405);
    $this->actingAs($admin)->put('/audit-logs/1')->assertStatus(405);
    $this->actingAs($admin)->patch('/audit-logs/1')->assertStatus(405);
    $this->actingAs($admin)->delete('/audit-logs/1')->assertStatus(405);
});
