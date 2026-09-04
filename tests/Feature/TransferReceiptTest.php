<?php

declare(strict_types=1);

use App\Actions\Master\SubmitDocumentManifest;
use App\Models\DocumentLocation;
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

function manifestSiapTerimaUji(User $admin, int $qty = 2, string $condition = 'GOOD'): array
{
    $manifest = manifestDraftUji($admin);
    $dokumen = dokumenManifestUji($admin);
    $manifest->items()->create([
        'document_id' => $dokumen->getKey(),
        'qty_sent' => $qty,
        'condition_sent' => $condition,
        'sent_note' => null,
    ]);

    app(SubmitDocumentManifest::class)->handle($manifest, $admin);

    return [$manifest->refresh(), $dokumen->refresh()];
}

function payloadTerimaUji(int $transferItemId, int $qty, string $condition = 'GOOD', string $status = 'COMPLETE', ?string $note = null): array
{
    return [
        'items' => [
            $transferItemId => [
                'id' => $transferItemId,
                'qty_received' => $qty,
                'condition_received' => $condition,
                'receipt_status' => $status,
                'receipt_note' => $note,
            ],
        ],
    ];
}

test('terima lengkap tanpa catatan menjadi complete', function (): void {
    $admin = adminDokumenUji();
    [$manifest, $dokumen] = manifestSiapTerimaUji($admin, 2);
    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();
    $lokasi = DocumentLocation::where('code', 'LEMARI-CONTOH-A1')->firstOrFail();

    $this->actingAs($admin)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->id, 2) + ['document_location_id' => $lokasi->id]
    )->assertRedirect(route('document_manifests.show', $manifest));

    expect($manifest->refresh()->status)->toBe('RECEIVED_COMPLETE');
    expect($transfer->refresh()->transfer_status)->toBe('RECEIVED');
    expect($transfer->refresh()->receipt_result)->toBe('COMPLETE');
    expect($dokumen->refresh()->status)->toBe('RECEIVED');

    $holder = $dokumen->holder()->firstOrFail();
    expect($holder->holder_type)->toBe('WORK_UNIT');
    expect($holder->work_unit_id)->toBe(WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->id);
    expect((int) $holder->document_location_id)->toBe((int) $lokasi->id);
});

test('terima penuh dengan catatan menjadi noted', function (): void {
    $admin = adminDokumenUji();
    [$manifest, $dokumen] = manifestSiapTerimaUji($admin, 1);

    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();

    $this->actingAs($admin)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->id, 1, 'DAMAGED', 'COMPLETE', 'Catatan terima uji')
    )->assertRedirect();

    expect($manifest->refresh()->status)->toBe('RECEIVED_NOTED');
    expect($transfer->refresh()->receipt_result)->toBe('NOTED');
    expect($dokumen->refresh()->status)->toBe('RECEIVED');
    expect($dokumen->holder->condition_code)->toBe('DAMAGED');
});

test('terima sebagian menjadi partial dan holder pindah', function (): void {
    $admin = adminDokumenUji();
    [$manifest, $dokumen] = manifestSiapTerimaUji($admin, 4);

    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();

    $this->actingAs($admin)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->id, 2, 'GOOD', 'PARTIAL', 'Kurang dua uji')
    )->assertRedirect();

    expect($manifest->refresh()->status)->toBe('RECEIVED_PARTIAL');
    expect($transfer->refresh()->receipt_result)->toBe('PARTIAL');
    expect($dokumen->refresh()->status)->toBe('RECEIVED_PARTIAL');
    expect($dokumen->holder->work_unit_id)->toBe(
        WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->id
    );
});

test('qty nol membuat dokumen tetap transit dan holder sosial', function (): void {
    $admin = adminDokumenUji();
    [$manifest, $dokumen] = manifestSiapTerimaUji($admin, 3);

    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();

    $this->actingAs($admin)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->id, 0, 'GOOD', 'NOT_RECEIVED', 'Tidak datang uji')
    )->assertRedirect();

    expect($manifest->refresh()->status)->toBe('RECEIVED_PARTIAL');
    expect($dokumen->refresh()->status)->toBe('IN_TRANSIT');
    expect($dokumen->holder->work_unit_id)->toBe(
        WorkUnit::where('code', 'SOSIAL')->firstOrFail()->id
    );
});

test('tolak semua membuat manifest rejected dan dokumen kembali', function (): void {
    $admin = adminDokumenUji();
    [$manifest, $dokumen] = manifestSiapTerimaUji($admin, 1);
    $sosialId = WorkUnit::where('code', 'SOSIAL')->firstOrFail()->id;

    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();

    $this->actingAs($admin)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->id, 0, 'DAMAGED', 'REJECTED', 'Tolak uji') + ['receipt_note' => 'Tolak semua uji']
    )->assertRedirect();

    expect($manifest->refresh()->status)->toBe('REJECTED');
    expect($transfer->refresh()->transfer_status)->toBe('REJECTED');
    expect($transfer->refresh()->receipt_result)->toBe('REJECTED');
    expect($dokumen->refresh()->status)->toBe('REGISTERED');
    expect($dokumen->holder->work_unit_id)->toBe($sosialId);
    expect(
        $dokumen->holderHistories()->where('movement_type', 'MANIFEST_RECEIVED')->exists()
    )->toBeFalse();
});

test('catatan wajib bila status bukan complete dan qty valid', function (): void {
    $admin = adminDokumenUji();
    [$manifest] = manifestSiapTerimaUji($admin, 2);

    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();

    $this->actingAs($admin)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->id, 1, 'GOOD', 'PARTIAL', null)
    )->assertSessionHasErrors('items');

    $this->actingAs($admin)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->id, 9, 'GOOD', 'COMPLETE', null)
    )->assertSessionHasErrors('items');

    expect($manifest->refresh()->status)->toBe('SUBMITTED');
});

test('terima manifest draft tanpa transfer menghasilkan 404', function (): void {
    $admin = adminDokumenUji();
    $manifest = manifestDraftUji($admin);

    $this->actingAs($admin)->get(route('document_transfers.show', $manifest))->assertNotFound();
    $this->actingAs($admin)->get(route('document_transfers.edit', $manifest))->assertNotFound();
});
