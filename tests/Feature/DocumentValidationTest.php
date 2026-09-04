<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\Document;
use App\Models\DocumentType;
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

function payloadDokumenUji(array $overrides = []): array
{
    $type = DocumentType::where('code', 'KUESIONER')->firstOrFail();
    $alokasi = Allocation::where('nks', 'NKS-2099-001')->firstOrFail();

    return array_merge([
        'document_type_id' => $type->getKey(),
        'allocation_id' => $alokasi->getKey(),
        'dsrt_sample_id' => null,
        'title' => 'Berkas Valid Uji',
        'format' => 'PHYSICAL',
        'quantity' => 2,
        'condition_code' => 'GOOD',
    ], $overrides);
}

test('buat dokumen valid dengan tepat satu konteks', function (): void {
    $admin = adminDokumenUji();

    $response = $this->actingAs($admin)->post(route('documents.store'), payloadDokumenUji());

    $response->assertRedirect(route('documents.index'));
    expect(Document::where('title', 'Berkas Valid Uji')->exists())->toBeTrue();
});

test('xor konteks ditolak dua-duanya kosong atau dua-duanya terisi', function (): void {
    $admin = adminDokumenUji();
    $alokasi = Allocation::where('nks', 'NKS-2099-001')->firstOrFail();

    $this->actingAs($admin)->post(
        route('documents.store'),
        payloadDokumenUji(['allocation_id' => null, 'dsrt_sample_id' => null])
    )->assertSessionHasErrors('allocation_id');

    $this->actingAs($admin)->post(
        route('documents.store'),
        payloadDokumenUji(['allocation_id' => $alokasi->getKey(), 'dsrt_sample_id' => 999999])
    )->assertSessionHasErrors('dsrt_sample_id');

    expect(Document::count())->toBe(0);
});

test('quantity minimum 1 dan format hanya physical', function (): void {
    $admin = adminDokumenUji();

    $this->actingAs($admin)->post(
        route('documents.store'),
        payloadDokumenUji(['quantity' => 0])
    )->assertSessionHasErrors('quantity');

    $this->actingAs($admin)->post(
        route('documents.store'),
        payloadDokumenUji(['format' => 'DIGITAL'])
    )->assertSessionHasErrors('format');

    expect(Document::count())->toBe(0);
});

test('update non-registered ditolak dan kode immutable', function (): void {
    $admin = adminDokumenUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-VAL-01');
    $dokumen = dokumenSiapUji($admin, $alokasi, 'Berkas Validasi Uji');
    $dokumen->update(['status' => 'IN_TRANSIT']);

    $this->actingAs($admin)->put(route('documents.update', $dokumen), [
        'title' => 'Coba Ubah Uji',
        'quantity' => 1,
    ])->assertSessionHasErrors('status');

    expect($dokumen->refresh()->title)->toBe('Berkas Validasi Uji');
});

test('type dan location code duplikat atau regex ditolak', function (): void {
    $admin = adminDokumenUji();

    $this->actingAs($admin)->post(route('document_types.store'), [
        'code' => 'KUESIONER', 'name' => 'Ganda Uji',
    ])->assertSessionHasErrors('code');

    $this->actingAs($admin)->post(route('document_types.store'), [
        'code' => 'kode kecil!', 'name' => 'Regex Uji',
    ])->assertSessionHasErrors('code');

    $this->actingAs($admin)->post(route('document_locations.store'), [
        'code' => 'LEMARI-CONTOH-A1', 'name' => 'Ganda Uji',
    ])->assertSessionHasErrors('code');
});
