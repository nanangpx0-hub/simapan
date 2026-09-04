<?php

declare(strict_types=1);

use App\Models\DocumentManifest;
use App\Models\WorkUnit;
use App\Support\ManifestNumber;
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

test('halaman penugasan dokumen merender pilihan petugas dan lokasi tanpa error', function (): void {
    $admin = adminDokumenUji();
    $document = dokumenSiapUji($admin, alokasiSusenasUji($admin, 'NKS-PENUGASAN-UI'));

    $response = $this->actingAs($admin)->get(route('document_processing_assignments.index', $document));

    $response->assertOk();
    $response->assertSee('Petugas');
});

test('tautan serah terima disembunyikan untuk manifest draft tanpa transfer', function (): void {
    $admin = adminDokumenUji();
    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();

    $manifest = DocumentManifest::create([
        'manifest_number' => ManifestNumber::next(),
        'from_work_unit_id' => $sosial->getKey(),
        'to_work_unit_id' => $olah->getKey(),
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);

    $response = $this->actingAs($admin)->get(route('document_manifests.show', $manifest));

    $response->assertOk();
    $response->assertDontSee('Serah terima');
});
