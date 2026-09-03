<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
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

function periodeAksiUji(User $admin, SurveyType $type, string $code): SurveyPeriod
{
    return SurveyPeriod::create([
        'code' => $code, 'survey_type_id' => $type->getKey(), 'name' => 'Aksi Uji',
        'period_type' => 'SEMESTER', 'period_number' => 1, 'year' => 2099,
        'start_date' => '2099-01-01', 'end_date' => '2099-06-30',
        'status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);
}

test('aktivasi penutupan dan pengarsipan periode tercatat', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $type = SurveyType::create(['code' => 'AKSI', 'name' => 'Aksi Uji', 'is_active' => true]);
    $period = periodeAksiUji($admin, $type, 'AKSI-01');

    $this->actingAs($admin)->post(route('master.survey_periods.activate', $period))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.close', $period))->assertRedirect();
    $this->actingAs($admin)->post(route('master.survey_periods.archive', $period))->assertRedirect();

    $actions = AuditLog::where('auditable_type', SurveyPeriod::class)
        ->where('auditable_id', $period->id)->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe(['created', 'activated', 'closed', 'archived']);
});

test('nonaktifkan officer tercatat deactivated dan parent work unit tercatat', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $unit = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $officer = Officer::create(['code' => 'AKSI-O1', 'name' => 'Aksi O Uji', 'work_unit_id' => $unit->getKey()]);

    $this->actingAs($admin)->put(route('master.officers.update', $officer), [
        'name' => 'Aksi O Uji', 'work_unit_id' => $unit->getKey(), 'status' => 'INACTIVE',
    ])->assertRedirect();

    expect(AuditLog::where('auditable_type', Officer::class)->where('auditable_id', $officer->id)->where('action', 'deactivated')->count())->toBe(1);

    $child = WorkUnit::create(['code' => 'AKSI-WC', 'name' => 'Anak Aksi Uji', 'is_active' => true]);
    $this->actingAs($admin)->put(route('master.unit-kerja.update', $child), [
        'name' => 'Anak Aksi Uji', 'parent_id' => $unit->id,
    ])->assertRedirect();

    $actions = AuditLog::where('auditable_type', WorkUnit::class)
        ->where('auditable_id', $child->id)->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe(['created', 'parent_changed']);
});

test('assign dan remove role tercatat dengan slug aman', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Target Aksi Uji',
        'email' => 'target-aksi-uji@simapan.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_active' => true,
        'roles' => ['viewer'],
    ])->assertRedirect();

    $target = User::where('email', 'target-aksi-uji@simapan.test')->firstOrFail();

    $assigned = AuditLog::where('auditable_type', User::class)
        ->where('auditable_id', $target->id)->where('action', 'role_assigned')->firstOrFail();

    expect($assigned->metadata['role_slug'])->toBe('viewer');

    $this->actingAs($admin)->put(route('admin.users.update', $target), [
        'name' => 'Target Aksi Uji',
        'email' => 'target-aksi-uji@simapan.test',
        'is_active' => true,
        'roles' => ['field_officer'],
    ])->assertRedirect();

    $removed = AuditLog::where('auditable_type', User::class)
        ->where('auditable_id', $target->id)->where('action', 'role_removed')->firstOrFail();

    expect($removed->metadata['role_slug'])->toBe('viewer');

    $raw = json_encode([$assigned->metadata, $removed->metadata]);
    expect($raw)->not->toContain('password');
});

test('login sukses logout dan gagal tercatat aman', function (): void {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $this->assertAuthenticated();

    $this->post('/logout');
    $this->assertGuest();

    $this->post('/login', ['email' => $user->email, 'password' => 'salah-uji-01']);
    $this->assertGuest();

    $login = AuditLog::where('action', 'login_succeeded')->firstOrFail();
    expect((int) $login->user_id)->toBe((int) $user->id);

    $logout = AuditLog::where('action', 'logout')->firstOrFail();
    expect((int) $logout->user_id)->toBe((int) $user->id);

    $failed = AuditLog::where('action', 'login_failed')->firstOrFail();
    expect($failed->user_id)->toBeNull();

    $raw = json_encode([$login->metadata, $logout->metadata, $failed->old_values, $failed->new_values, $failed->metadata]);
    expect($raw)->not->toContain($user->email)->and($raw)->not->toContain('salah-uji-01');
});

test('penolakan self-role tidak mencatat perubahan sukses', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $before = AuditLog::where('auditable_type', User::class)->where('auditable_id', $admin->id)->count();

    $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'is_active' => true,
        'roles' => ['viewer'],
    ])->assertForbidden();

    expect(AuditLog::where('auditable_type', User::class)->where('auditable_id', $admin->id)->count())->toBe($before);
});
