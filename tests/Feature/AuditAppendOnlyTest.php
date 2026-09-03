<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\SurveyType;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

test('model audit log menolak update dan delete', function (): void {
    $log = AuditLog::create([
        'event_uuid' => (string) Str::uuid(),
        'action' => 'created',
        'auditable_type' => SurveyType::class,
        'auditable_id' => 1,
        'new_values' => ['code' => 'KEKAL'],
    ]);

    try {
        $log->update(['action' => 'updated']);
        $this->fail('Update audit log seharusnya ditolak.');
    } catch (RuntimeException) {
        expect(true)->toBeTrue();
    }

    try {
        $log->delete();
        $this->fail('Delete audit log seharusnya ditolak.');
    } catch (RuntimeException) {
        expect(true)->toBeTrue();
    }

    expect($log->refresh()->action)->toBe('created');
    expect(AuditLog::where('id', $log->id)->exists())->toBeTrue();
});

test('audit pelaku tercatat dan bertahan saat user dihapus', function (): void {
    $adminA = User::factory()->create();
    $adminA->assignRole('administrator');
    $adminB = User::factory()->create();
    $adminB->assignRole('administrator');

    $this->actingAs($adminA)->post(route('master.jenis-survei.store'), [
        'code' => 'PENULIS', 'name' => 'Penulis Uji',
    ])->assertRedirect();

    $log = AuditLog::where('action', 'created')
        ->where('auditable_type', SurveyType::class)->firstOrFail();
    expect((int) $log->user_id)->toBe((int) $adminA->id);
    expect($log->actorName())->toBe($adminA->name);

    $this->actingAs($adminB)->delete(route('admin.users.destroy', $adminA))->assertRedirect();

    $log->refresh();
    expect($log->user_id)->toBeNull();
    expect($log->actorName())->toBe('System/Unknown');
    expect(AuditLog::where('id', $log->id)->exists())->toBeTrue();
});

test('daftar terbaru dahulu dan filter bekerja', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $this->actingAs($admin)->post(route('master.jenis-survei.store'), [
        'code' => 'URUT-01', 'name' => 'Urut Satu Uji',
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('master.jenis-survei.store'), [
        'code' => 'URUT-02', 'name' => 'Urut Dua Uji',
    ])->assertRedirect();

    $response = $this->actingAs($admin)->get('/audit-logs');
    $response->assertOk();

    $ids = AuditLog::orderByDesc('id')->pluck('id')->all();
    expect($ids)->not->toBeEmpty();
    expect($ids)->toBe(collect($ids)->sortDesc()->values()->all());

    $this->actingAs($admin)->get('/audit-logs?action=created')->assertOk()->assertSee('Detail');
    $this->actingAs($admin)->get('/audit-logs?action=logout')->assertOk()->assertDontSee('Detail');
    $this->actingAs($admin)->get('/audit-logs?auditable_type='.urlencode(SurveyType::class))->assertOk()->assertSee('Detail');
    $this->actingAs($admin)->get('/audit-logs?user_id='.$admin->id)->assertOk()->assertSee('Detail');
});

test('detail menampilkan data tersanitasi', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::create(['code' => 'SANI', 'name' => 'Sani Uji', 'is_active' => true]);
    $officer = Officer::create([
        'code' => 'SANI-01', 'name' => 'Sani Officer Uji', 'work_unit_id' => $unit->getKey(),
        'phone' => '0800000001', 'email' => 'sani-uji@simapan.test',
    ]);

    $log = AuditLog::where('auditable_type', Officer::class)
        ->where('auditable_id', $officer->id)->where('action', 'created')->firstOrFail();

    $response = $this->actingAs($admin)->get(route('audit_logs.show', $log));

    $response->assertOk();
    $response->assertDontSee('0800000001', false);
    $response->assertDontSee('sani-uji@simapan.test', false);
    $response->assertSee('SANI-01');
});
