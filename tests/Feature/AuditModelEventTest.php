<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\OfficerAlias;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkUnitSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        WorkUnitSeeder::class,
    ]);
});

test('create dan update survey type diaudit', function (): void {
    $type = SurveyType::create(['code' => 'AUDT', 'name' => 'Audit Uji', 'is_active' => true]);
    $type->update(['name' => 'Audit Ubah Uji']);

    $logs = AuditLog::where('auditable_type', SurveyType::class)
        ->where('auditable_id', $type->id)->orderBy('id')->get();

    expect($logs->pluck('action')->all())->toBe(['created', 'updated']);
    expect($logs[0]->new_values['code'])->toBe('AUDT');
    expect($logs[1]->old_values['name'])->toBe('Audit Uji');
    expect($logs[1]->new_values['name'])->toBe('Audit Ubah Uji');
});

test('create dan update work unit diaudit', function (): void {
    $unit = WorkUnit::create(['code' => 'AUDW', 'name' => 'Audit W Uji', 'is_active' => true]);
    $unit->update(['name' => 'Audit W Ubah Uji']);

    expect(AuditLog::where('auditable_type', WorkUnit::class)->where('auditable_id', $unit->id)->count())->toBe(2);
});

test('create dan update region diaudit', function (): void {
    $region = Region::create([
        'parent_id' => null, 'level' => 'PROVINSI', 'code' => 'AUDR',
        'name' => 'Audit R Uji', 'is_active' => true,
    ]);
    $region->update(['name' => 'Audit R Ubah Uji']);

    expect(AuditLog::where('auditable_type', Region::class)->where('auditable_id', $region->id)->count())->toBe(2);
});

test('create dan update survey period diaudit', function (): void {
    $admin = User::factory()->create();
    $type = SurveyType::create(['code' => 'AUDP', 'name' => 'Audit P Uji', 'is_active' => true]);
    $period = SurveyPeriod::create([
        'code' => 'AUDP-01', 'survey_type_id' => $type->getKey(), 'name' => 'Periode Audit Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
    $period->update(['name' => 'Periode Audit Ubah Uji']);

    $logs = AuditLog::where('auditable_type', SurveyPeriod::class)
        ->where('auditable_id', $period->id)->orderBy('id')->get();

    expect($logs->pluck('action')->all())->toBe(['created', 'updated']);
    expect($logs[1]->new_values['name'])->toBe('Periode Audit Ubah Uji');
});

test('officer diaudit tanpa phone email', function (): void {
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create([
        'code' => 'AUDO-01', 'name' => 'Audit O Uji', 'work_unit_id' => $unit->getKey(),
        'phone' => '0800000001', 'email' => 'audit-o-uji@simapan.test',
    ]);
    $officer->update(['name' => 'Audit O Ubah Uji']);

    $logs = AuditLog::where('auditable_type', Officer::class)
        ->where('auditable_id', $officer->id)->orderBy('id')->get();

    expect($logs->count())->toBe(2);

    foreach ($logs as $log) {
        expect(array_keys($log->new_values ?? []))->not->toContain('phone', 'email');
        expect(array_keys($log->old_values ?? []))->not->toContain('phone', 'email');
    }

    $raw = json_encode([$logs[0]->new_values, $logs[1]->old_values, $logs[1]->new_values]);
    expect($raw)->not->toContain('0800000001')->and($raw)->not->toContain('audit-o-uji@simapan.test');
});

test('alias diaudit dan user diaudit tanpa kredensial', function (): void {
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create(['code' => 'AUDA-01', 'name' => 'Audit A Uji', 'work_unit_id' => $unit->getKey()]);
    $alias = $officer->aliases()->create(['alias_name' => 'Alias Audit Uji']);
    $alias->update(['alias_name' => 'Alias Audit Ubah Uji']);

    expect(AuditLog::where('auditable_type', OfficerAlias::class)->where('auditable_id', $alias->id)->count())->toBe(2);

    $user = User::factory()->create(['name' => 'Audit User Uji']);
    $user->update(['name' => 'Audit User Ubah Uji']);

    $logs = AuditLog::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)->orderBy('id')->get();

    expect($logs->count())->toBe(2);

    $raw = json_encode([$logs[0]->new_values, $logs[1]->old_values, $logs[1]->new_values]);
    expect($raw)->not->toContain('password')
        ->and($raw)->not->toContain($user->email)
        ->and($raw)->not->toContain('remember_token');
    expect($logs[1]->new_values['name'])->toBe('Audit User Ubah Uji');
});

test('satu aksi model terkontrol hanya satu log', function (): void {
    $before = AuditLog::count();

    SurveyType::create(['code' => 'SATU', 'name' => 'Satu Uji', 'is_active' => true]);

    expect(AuditLog::count())->toBe($before + 1);
});

test('audit log tidak mengaudit dirinya sendiri', function (): void {
    AuditLog::create([
        'event_uuid' => (string) Str::uuid(),
        'action' => 'created',
        'auditable_type' => SurveyType::class,
        'auditable_id' => 1,
    ]);

    expect(AuditLog::where('auditable_type', AuditLog::class)->count())->toBe(0);
});
