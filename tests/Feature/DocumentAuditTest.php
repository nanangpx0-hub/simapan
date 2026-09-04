<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentManifest;
use App\Models\DocumentType;
use App\Models\Officer;
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

test('create dan update dokumen satu audit allowlist', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-AUD-01');
    $type = DocumentType::where('code', 'KUESIONER')->firstOrFail();

    $this->actingAs($admin)->post(route('documents.store'), [
        'document_type_id' => $type->id,
        'allocation_id' => $alokasi->id,
        'title' => 'Berkas Audit Uji',
        'format' => 'PHYSICAL',
        'quantity' => 1,
    ])->assertRedirect();

    $dokumen = Document::where('title', 'Berkas Audit Uji')->firstOrFail();

    $this->actingAs($admin)->put(route('documents.update', $dokumen), [
        'title' => 'Berkas Audit Ubah Uji',
        'quantity' => 2,
    ])->assertRedirect();

    $actions = AuditLog::where('auditable_type', Document::class)
        ->where('auditable_id', $dokumen->id)->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe(['created', 'updated']);
});

test('submit receive assign tepat satu event workflow', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-AUD-02');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Alur Uji');

    $manifest = manifestDraftUji($admin);
    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $dokumen->id,
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))->assertRedirect();

    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();
    $this->actingAs($admin)->put(route('document_transfers.update', $manifest), [
        'items' => [
            $item->id => [
                'id' => $item->id,
                'qty_received' => 1,
                'condition_received' => 'GOOD',
                'receipt_status' => 'COMPLETE',
                'receipt_note' => null,
            ],
        ],
    ])->assertRedirect();

    $officer = Officer::where('code', 'OFF-002')->firstOrFail();
    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->id,
        'condition_code' => 'GOOD',
    ])->assertRedirect();

    $docActions = AuditLog::where('auditable_type', Document::class)
        ->where('auditable_id', $dokumen->id)->orderBy('id')->pluck('action')->all();

    expect($docActions)->toBe(['created', 'submitted', 'received_complete', 'assigned']);

    $manifestActions = AuditLog::where('auditable_type', DocumentManifest::class)
        ->where('auditable_id', $manifest->id)->orderBy('id')->pluck('action')->all();

    expect($manifestActions)->toBe(['created', 'submitted', 'received_complete']);
});

test('audit rollback bila operasi bisnis gagal', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-AUD-03');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Gagal Uji');
    $before = AuditLog::where('auditable_type', Document::class)->where('auditable_id', $dokumen->id)->count();

    $manifest = manifestDraftUji($admin);

    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))
        ->assertSessionHasErrors('status');

    expect(AuditLog::where('auditable_type', DocumentManifest::class)->where('auditable_id', $manifest->id)->count())->toBe(1);
    expect(AuditLog::where('auditable_type', Document::class)->where('auditable_id', $dokumen->id)->count())->toBe($before);
});
