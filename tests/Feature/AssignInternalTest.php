<?php

declare(strict_types=1);

use App\Actions\Master\ReceiveDocumentManifest;
use App\Actions\Master\SubmitDocumentManifest;
use App\Models\Document;
use App\Models\DocumentProcessingAssignment;
use App\Models\Officer;
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

function dokumenReceivedUji(User $admin, string $nks = 'NKS-DOK-ASN-01'): Document
{
    $alokasi = alokasiSusenasUji($admin, $nks);
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Assign Uji');

    $manifest = manifestDraftUji($admin);
    $manifest->items()->create([
        'document_id' => $dokumen->getKey(),
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ]);

    app(SubmitDocumentManifest::class)->handle($manifest, $admin);

    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();

    app(ReceiveDocumentManifest::class)->handle(
        $manifest,
        [$item->getKey() => [
            'qty_received' => 1,
            'condition_received' => 'GOOD',
            'receipt_status' => 'COMPLETE',
            'receipt_note' => null,
        ]],
        null,
        null,
        $admin
    );

    return $dokumen->refresh();
}

test('hanya dokumen received dengan holder pengolahan dapat ditugaskan', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-ASN-02');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Belum Uji');
    $officer = Officer::where('code', 'OFF-002')->firstOrFail();

    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->id,
        'condition_code' => 'GOOD',
    ])->assertSessionHasErrors('document');

    expect(DocumentProcessingAssignment::where('document_id', $dokumen->id)->count())->toBe(0);
});

test('officer harus aktif unit pengolahan dan tidak terhapus', function (): void {
    $admin = adminDokumenUji();
    $dokumen = dokumenReceivedUji($admin, 'NKS-DOK-ASN-03');

    $sosial = Officer::where('code', 'OFF-001')->firstOrFail();
    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $sosial->id,
        'condition_code' => 'GOOD',
    ])->assertSessionHasErrors('officer_id');

    $mati = Officer::where('code', 'OFF-003')->firstOrFail();
    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $mati->id,
        'condition_code' => 'GOOD',
    ])->assertSessionHasErrors('officer_id');

    $hapus = Officer::create([
        'code' => 'OFF-HAPUS-01',
        'name' => 'Petugas Hapus Uji',
        'work_unit_id' => WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->getKey(),
    ]);
    $hapus->delete();

    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $hapus->id,
        'condition_code' => 'GOOD',
    ])->assertSessionHasErrors('officer_id');

    expect(DocumentProcessingAssignment::where('document_id', $dokumen->id)->count())->toBe(0);
});

test('assign sukses memindahkan holder dan status', function (): void {
    $admin = adminDokumenUji();
    $dokumen = dokumenReceivedUji($admin, 'NKS-DOK-ASN-04');
    $officer = Officer::where('code', 'OFF-002')->firstOrFail();

    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->id,
        'condition_code' => 'GOOD',
        'note' => 'Tugas uji',
    ])->assertRedirect(route('document_processing_assignments.index', $dokumen));

    expect($dokumen->refresh()->status)->toBe('ASSIGNED_PROCESSING');

    $assignment = DocumentProcessingAssignment::where('document_id', $dokumen->id)->firstOrFail();
    expect($assignment->status)->toBe('ACTIVE');
    expect((int) $assignment->officer_id)->toBe((int) $officer->id);

    $holder = $dokumen->holder()->firstOrFail();
    expect($holder->holder_type)->toBe('OFFICER');
    expect((int) $holder->officer_id)->toBe((int) $officer->id);

    expect(
        $dokumen->holderHistories()->where('movement_type', 'PROCESSING_ASSIGNED')->count()
    )->toBe(1);
});

test('satu assignment aktif per dokumen', function (): void {
    $admin = adminDokumenUji();
    $dokumen = dokumenReceivedUji($admin, 'NKS-DOK-ASN-05');
    $officer = Officer::where('code', 'OFF-002')->firstOrFail();

    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->id,
        'condition_code' => 'GOOD',
    ])->assertRedirect();

    $lain = Officer::create([
        'code' => 'OFF-LAIN-01',
        'name' => 'Petugas Lain Uji',
        'work_unit_id' => WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->getKey(),
    ]);

    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $lain->id,
        'condition_code' => 'GOOD',
    ])->assertSessionHasErrors('document');

    expect(
        DocumentProcessingAssignment::where('document_id', $dokumen->id)->where('status', 'ACTIVE')->count()
    )->toBe(1);
});

test('assign dokumen non-received ditolak', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-ASN-06');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Belum Uji');
    $officer = Officer::where('code', 'OFF-002')->firstOrFail();

    $this->actingAs($admin)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->id,
        'condition_code' => 'GOOD',
    ])->assertSessionHasErrors('document');

    expect(DocumentProcessingAssignment::where('document_id', $dokumen->id)->count())->toBe(0);
    expect($dokumen->refresh()->status)->toBe('REGISTERED');
});
