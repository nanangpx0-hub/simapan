<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\DsrtSample;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        RegionSeeder::class,
        SurveyPeriodSeeder::class,
    ]);
});

test('create dan update dsrt menghasilkan audit allowlist', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-AU-01');

    $this->actingAs($admin)->post(route('allocations.dsrt.store', $alokasi), [
        'nus' => 'NUS-AU-01', 'nurt' => 'NURT-AU-01', 'krt_name' => 'KRT Au Uji',
    ])->assertRedirect();

    $sample = DsrtSample::where('nus', 'NUS-AU-01')->firstOrFail();

    $this->actingAs($admin)->put(route('allocations.dsrt.update', [$alokasi, $sample]), [
        'krt_name' => 'KRT Au Ubah Uji', 'enumeration_status' => 'IN_PROGRESS',
    ])->assertRedirect();

    $actions = AuditLog::where('auditable_type', DsrtSample::class)
        ->where('auditable_id', $sample->id)->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe(['created', 'updated']);

    $updated = AuditLog::where('auditable_type', DsrtSample::class)
        ->where('auditable_id', $sample->id)->where('action', 'updated')->firstOrFail();

    expect($updated->new_values['krt_name'])->toBe('KRT Au Ubah Uji');
    expect(array_keys($updated->new_values))->not->toContain('address', 'contact_person', 'contact_phone', 'notes');
});

test('verify dan archive masing-masing satu audit tanpa duplikat', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-AU-02');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-AU-02', 'nurt' => 'NURT-AU-02', 'krt_name' => 'KRT Au Uji',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasi, $sample]))->assertRedirect();
    $this->actingAs($admin)->post(route('allocations.dsrt.archive', [$alokasi, $sample]))->assertRedirect();

    $actions = AuditLog::where('auditable_type', DsrtSample::class)
        ->where('auditable_id', $sample->id)->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe(['created', 'verified', 'archived']);

    $verified = AuditLog::where('auditable_type', DsrtSample::class)
        ->where('auditable_id', $sample->id)->where('action', 'verified')->firstOrFail();
    expect($verified->metadata['status_before'])->toBe('DRAFT');
    expect($verified->metadata['status_after'])->toBe('VERIFIED');
});

test('audit rollback bila action bisnis gagal', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-AU-03');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-AU-03', 'nurt' => 'NURT-AU-03', 'krt_name' => 'KRT Au Uji',
        'record_status' => 'ARCHIVED', 'created_by' => $admin->getKey(),
    ]);
    $before = AuditLog::where('auditable_type', DsrtSample::class)->where('auditable_id', $sample->id)->count();

    $this->actingAs($admin)->post(route('allocations.dsrt.verify', [$alokasi, $sample]))
        ->assertSessionHasErrors('record_status');

    expect(AuditLog::where('auditable_type', DsrtSample::class)->where('auditable_id', $sample->id)->count())->toBe($before);
    expect($sample->refresh()->record_status)->toBe('ARCHIVED');
});
