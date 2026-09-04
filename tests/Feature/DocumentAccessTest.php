<?php

declare(strict_types=1);

use App\Models\DocumentManifest;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\AllocationSeeder;
use Database\Seeders\DocumentMasterSeeder;
use Database\Seeders\OfficerSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;
use Database\Seeders\WorkUnitSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        WorkUnitSeeder::class,
        RegionSeeder::class,
        OfficerSeeder::class,
        SurveyPeriodSeeder::class,
        AllocationSeeder::class,
        DocumentMasterSeeder::class,
    ]);
});

test('guest diarahkan ke login pada halaman dokumen', function (): void {
    $this->get('/dokumen')->assertRedirect('/login');
    $this->get('/dokumen/jenis')->assertRedirect('/login');
    $this->get('/dokumen/lokasi')->assertRedirect('/login');
    $this->get('/manifest')->assertRedirect('/login');
});

test('user tanpa document.view mendapat 403', function (): void {
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $this->actingAs($user)->get('/dokumen')->assertForbidden();
    $this->actingAs($user)->get('/dokumen/jenis')->assertForbidden();
    $this->actingAs($user)->get('/dokumen/lokasi')->assertForbidden();
    $this->actingAs($user)->get('/manifest')->assertForbidden();
});

test('user tanpa document.manage mendapat 403 tulis', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('document.view');

    $this->actingAs($user)->get('/dokumen/create')->assertForbidden();
    $this->actingAs($user)->post(route('documents.store'), [
        'document_type_id' => 1,
        'title' => 'Coba Uji',
        'format' => 'PHYSICAL',
        'quantity' => 1,
    ])->assertForbidden();
    $this->actingAs($user)->get('/manifest/create')->assertForbidden();
});

test('user tanpa document.receive mendapat 403 serah terima', function (): void {
    $admin = adminDokumenUji();
    $manifest = DocumentManifest::create([
        'manifest_number' => 'DM-UJI-001',
        'from_work_unit_id' => WorkUnit::where('code', 'SOSIAL')->firstOrFail()->getKey(),
        'to_work_unit_id' => WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->getKey(),
        'status' => 'SUBMITTED',
        'created_by' => $admin->getKey(),
    ]);
    $manifest->transfer()->create([
        'transfer_status' => 'PENDING',
        'created_by' => $admin->getKey(),
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('document.view', 'document.manage');

    $this->actingAs($user)->get(route('document_transfers.show', $manifest))->assertForbidden();
    $this->actingAs($user)->get(route('document_transfers.edit', $manifest))->assertForbidden();
});

test('user tanpa document.assign mendapat 403 penugasan', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-AKS-01');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Akses Uji');

    $user = User::factory()->create();
    $user->givePermissionTo('document.view', 'document.manage', 'document.receive');

    $this->actingAs($user)->get(route('document_processing_assignments.index', $dokumen))->assertForbidden();
    $this->actingAs($user)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => 1,
        'condition_code' => 'GOOD',
    ])->assertForbidden();
});

test('administrator dapat membuka semua halaman dokumen', function (): void {
    $admin = adminDokumenUji();

    $this->actingAs($admin)->get('/dokumen')->assertOk();
    $this->actingAs($admin)->get('/dokumen/jenis')->assertOk();
    $this->actingAs($admin)->get('/dokumen/lokasi')->assertOk();
    $this->actingAs($admin)->get('/manifest')->assertOk();
    $this->actingAs($admin)->get('/dokumen/create')->assertOk();
});
