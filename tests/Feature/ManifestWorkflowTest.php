<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentManifest;
use App\Models\User;
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

function manifestDraftUji(User $admin): DocumentManifest
{
    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();

    return DocumentManifest::create([
        'manifest_number' => ManifestNumber::next(),
        'from_work_unit_id' => $sosial->getKey(),
        'to_work_unit_id' => $olah->getKey(),
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
}

function dokumenManifestUji(User $admin, string $title = 'Berkas Manifest Uji'): Document
{
    $alokasi = alokasiSusenasUji($admin, 'NKS-DOK-MNF-'.fake()->unique()->numerify('###'));

    return dokumenSiapUji($admin, $alokasi, $title);
}

test('buat manifest draft via http dan tambah item', function (): void {
    $admin = adminDokumenUji();
    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();

    $response = $this->actingAs($admin)->post(route('document_manifests.store'), [
        'from_work_unit_id' => $sosial->id,
        'to_work_unit_id' => $olah->id,
    ]);
    $response->assertRedirect();

    $manifest = DocumentManifest::orderByDesc('id')->firstOrFail();
    expect($manifest->status)->toBe('DRAFT');
    expect($manifest->manifest_number)->toStartWith('DM-');

    $this->actingAs($admin)->post(route('document_manifests.store'), [
        'from_work_unit_id' => $sosial->id,
        'to_work_unit_id' => $sosial->id,
    ])->assertSessionHasErrors('to_work_unit_id');

    $dokumen = dokumenManifestUji($admin);

    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $dokumen->id,
        'qty_sent' => 2,
        'condition_sent' => 'GOOD',
    ])->assertRedirect(route('document_manifests.show', $manifest));

    expect($manifest->refresh()->items()->count())->toBe(1);
});

test('submit manifest kosong ditolak', function (): void {
    $admin = adminDokumenUji();
    $manifest = manifestDraftUji($admin);

    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))
        ->assertSessionHasErrors('status');

    expect($manifest->refresh()->status)->toBe('DRAFT');
});

test('submit mengubah status dokumen holder dan transfer', function (): void {
    $admin = adminDokumenUji();
    $manifest = manifestDraftUji($admin);
    $dokumen = dokumenManifestUji($admin);

    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $dokumen->id,
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))->assertRedirect();

    expect($manifest->refresh()->status)->toBe('SUBMITTED');
    expect($dokumen->refresh()->status)->toBe('IN_TRANSIT');
    expect($manifest->transfer()->exists())->toBeTrue();
    expect($manifest->transfer->items()->count())->toBe(1);
    expect($dokumen->holder->work_unit_id)->toBe(
        WorkUnit::where('code', 'SOSIAL')->firstOrFail()->id
    );
});

test('submit kedua kali dan tambah item setelah submit ditolak', function (): void {
    $admin = adminDokumenUji();
    $manifest = manifestDraftUji($admin);
    $dokumen = dokumenManifestUji($admin);

    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $dokumen->id,
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))->assertRedirect();

    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))
        ->assertSessionHasErrors('status');

    $dokumen2 = dokumenManifestUji($admin, 'Berkas Kedua Uji');
    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $dokumen2->id,
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ])->assertSessionHasErrors('document_manifest_id');
});

test('batas 200 item per manifest', function (): void {
    $admin = adminDokumenUji();
    $manifest = manifestDraftUji($admin);

    for ($i = 1; $i <= 200; $i++) {
        $dokumen = dokumenManifestUji($admin, 'Berkas Batas Uji '.$i);
        $manifest->items()->create([
            'document_id' => $dokumen->getKey(),
            'qty_sent' => 1,
            'condition_sent' => 'GOOD',
        ]);
    }

    $lebih = dokumenManifestUji($admin, 'Berkas Lebih Uji');

    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $lebih->id,
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ])->assertSessionHasErrors('document_manifest_id');

    expect($manifest->refresh()->items()->count())->toBe(200);
});

test('tidak ada delete route manifest dan dokumen', function (): void {
    $admin = adminDokumenUji();
    $manifest = manifestDraftUji($admin);

    $this->actingAs($admin)->delete(route('document_manifests.show', $manifest))->assertStatus(405);
    $this->actingAs($admin)->delete('/dokumen/1')->assertStatus(405);
    expect(DocumentManifest::where('id', $manifest->id)->exists())->toBeTrue();
});

test('submit manifest dari SOSIAL ke IPDS berhasil', function (): void {
    $admin = adminDokumenUji();
    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $ipds = WorkUnit::where('code', 'IPDS')->firstOrFail();

    $manifest = DocumentManifest::create([
        'manifest_number' => ManifestNumber::next(),
        'from_work_unit_id' => $sosial->getKey(),
        'to_work_unit_id' => $ipds->getKey(),
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
    $dokumen = dokumenManifestUji($admin);
    $manifest->items()->create([
        'document_id' => $dokumen->getKey(),
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ]);

    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))->assertRedirect();

    expect($manifest->refresh()->status)->toBe('SUBMITTED');
    expect($manifest->transfer()->exists())->toBeTrue();
});

test('submit manifest dari unit bukan SOSIAL ditolak', function (): void {
    $admin = adminDokumenUji();
    $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();
    $ipds = WorkUnit::where('code', 'IPDS')->firstOrFail();

    $manifest = DocumentManifest::create([
        'manifest_number' => ManifestNumber::next(),
        'from_work_unit_id' => $olah->getKey(),
        'to_work_unit_id' => $ipds->getKey(),
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
    $dokumen = dokumenManifestUji($admin);
    $manifest->items()->create([
        'document_id' => $dokumen->getKey(),
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ]);

    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))
        ->assertSessionHasErrors('status');

    expect($manifest->refresh()->status)->toBe('DRAFT');
});

test('edit manifest setelah submit ditolak', function (): void {
    $admin = adminDokumenUji();
    $manifest = manifestDraftUji($admin);
    $dokumen = dokumenManifestUji($admin);
    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();

    $this->actingAs($admin)->post(route('document_manifests.items.store', $manifest), [
        'document_id' => $dokumen->id,
        'qty_sent' => 1,
        'condition_sent' => 'GOOD',
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('document_manifests.submit', $manifest))->assertRedirect();

    $this->actingAs($admin)->put(route('document_manifests.update', $manifest), [
        'from_work_unit_id' => $sosial->id,
        'to_work_unit_id' => $olah->id,
    ])->assertSessionHasErrors('status');

    expect($manifest->refresh()->from_work_unit_id)->toBe($sosial->id);
});
