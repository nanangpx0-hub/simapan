<?php

declare(strict_types=1);

use App\Models\Officer;
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

test('satu dokumen satu holder aktif', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-HLD-01');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Holder Uji');

    expect($dokumen->holder()->count())->toBe(1);
    expect($dokumen->holder->holder_type)->toBe('WORK_UNIT');
});

test('holder berpindah sosial pengolahan lalu officer', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-HLD-02');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Pindah Uji');
    $sosialId = WorkUnit::where('code', 'SOSIAL')->firstOrFail()->id;
    $olahId = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->id;
    $officer = Officer::where('code', 'OFF-002')->firstOrFail();

    expect((int) $dokumen->holder->work_unit_id)->toBe((int) $sosialId);

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

    expect((int) $dokumen->holder->refresh()->work_unit_id)->toBe((int) $olahId);

    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->id,
        'condition_code' => 'GOOD',
    ])->assertRedirect();

    $holder = $dokumen->holder()->firstOrFail();
    expect($holder->holder_type)->toBe('OFFICER');
    expect((int) $holder->officer_id)->toBe((int) $officer->id);
    expect($dokumen->holder()->count())->toBe(1);
});

test('riwayat holder tercatat berurutan', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-HLD-03');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Riwayat Uji');

    $movements = $dokumen->holderHistories()->orderBy('id')->pluck('movement_type')->all();

    expect($movements)->toBe(['REGISTERED']);

    $manifest = manifestDraftUji($admin);
    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $dokumen->id,
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))->assertRedirect();

    $movements = $dokumen->holderHistories()->orderBy('id')->pluck('movement_type')->all();

    expect($movements)->toBe(['REGISTERED', 'MANIFEST_SUBMITTED']);
});

test('tidak ada ui edit delete holder history', function (): void {
    $admin = adminDokumenUji();

    $this->actingAs($admin)->put('/dokumen/holder-history/1')->assertNotFound();
    $this->actingAs($admin)->delete('/dokumen/holder-history/1')->assertNotFound();
});
