<?php

declare(strict_types=1);

use App\Livewire\Master\DocumentManifestTable;
use App\Models\AuditLog;
use App\Models\DocumentLocation;
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
use Livewire\Livewire;

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

function ipdsOperatorUji(): User
{
    $user = User::factory()->create();
    $user->assignRole('ipds_operator');

    return $user;
}

function wasolahUji(): User
{
    $user = User::factory()->create();
    $user->assignRole('processing_supervisor');

    return $user;
}

test('ipds menerima manifest dari sosial dan mengalokasikan rak', function (): void {
    $admin = adminDokumenUji();
    [$manifest, $dokumen] = manifestSiapTerimaUji($admin, 2);
    $transfer = $manifest->transfer()->firstOrFail();
    $item = $transfer->items()->firstOrFail();
    $lokasi = DocumentLocation::where('code', 'LEMARI-CONTOH-A1')->firstOrFail();

    $ipds = ipdsOperatorUji();

    $this->actingAs($ipds)->get(route('document_transfers.edit', $manifest))->assertOk();

    $this->actingAs($ipds)->put(
        route('document_transfers.update', $manifest),
        payloadTerimaUji($item->getKey(), 2) + ['document_location_id' => $lokasi->getKey()]
    )->assertRedirect(route('document_manifests.show', $manifest));

    expect($manifest->refresh()->status)->toBe('RECEIVED_COMPLETE');
    expect($dokumen->refresh()->status)->toBe('RECEIVED');

    $holder = $dokumen->holder()->firstOrFail();
    expect($holder->holder_type)->toBe('WORK_UNIT');
    expect((int) $holder->work_unit_id)->toBe((int) WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->getKey());
    expect((int) $holder->document_location_id)->toBe((int) $lokasi->getKey());
});

test('ipds menugaskan dokumen ke petugas pengolahan yang valid', function (): void {
    $admin = adminDokumenUji();
    $dokumen = dokumenReceivedUji($admin, 'NKS-DOK-PLS-01');
    $officer = Officer::where('code', 'OFF-002')->firstOrFail();

    $ipds = ipdsOperatorUji();

    $this->actingAs($ipds)->get(route('document_processing_assignments.index', $dokumen))->assertOk();

    $this->actingAs($ipds)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->getKey(),
        'condition_code' => 'GOOD',
        'note' => 'Olah uji',
    ])->assertRedirect(route('document_processing_assignments.index', $dokumen));

    $assignment = DocumentProcessingAssignment::query()
        ->where('document_id', $dokumen->getKey())->active()->firstOrFail();

    expect((int) $assignment->officer_id)->toBe((int) $officer->getKey());
    expect((int) $assignment->assigned_by)->toBe((int) $ipds->getKey());
    expect($dokumen->refresh()->status)->toBe('ASSIGNED_PROCESSING');
});

test('penugasan ke petugas luar unit pengolahan ditolak', function (): void {
    $admin = adminDokumenUji();
    $dokumen = dokumenReceivedUji($admin, 'NKS-DOK-PLS-02');
    $sosial = Officer::where('code', 'OFF-001')->firstOrFail();

    $this->actingAs(ipdsOperatorUji())->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $sosial->getKey(),
        'condition_code' => 'GOOD',
    ])->assertSessionHasErrors('officer_id');

    expect(DocumentProcessingAssignment::where('document_id', $dokumen->getKey())->count())->toBe(0);
});

test('pengembalian dokumen mengubah status menjadi returned', function (): void {
    $admin = adminDokumenUji();
    $dokumen = dokumenReceivedUji($admin, 'NKS-DOK-PLS-03');
    $officer = Officer::where('code', 'OFF-002')->firstOrFail();
    $lokasi = DocumentLocation::where('code', 'LEMARI-CONTOH-A1')->firstOrFail();

    $wasolah = wasolahUji();

    $this->actingAs($wasolah)->post(route('document_processing_assignments.store', $dokumen), [
        'officer_id' => $officer->getKey(),
        'condition_code' => 'GOOD',
    ])->assertRedirect();

    $assignment = DocumentProcessingAssignment::query()
        ->where('document_id', $dokumen->getKey())->active()->firstOrFail();

    $this->actingAs($wasolah)->post(
        route('document_processing_assignments.return', [$dokumen, $assignment]),
        ['document_location_id' => $lokasi->getKey(), 'condition_code' => 'GOOD']
    )->assertRedirect(route('document_processing_assignments.index', $dokumen));

    $assignment = $assignment->refresh();
    expect($assignment->status)->toBe('RETURNED');
    expect($assignment->returned_at)->not->toBeNull();

    expect($dokumen->refresh()->status)->toBe('RECEIVED');

    $holder = $dokumen->holder()->firstOrFail();
    expect($holder->holder_type)->toBe('WORK_UNIT');
    expect((int) $holder->work_unit_id)->toBe((int) WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->getKey());
    expect((int) $holder->document_location_id)->toBe((int) $lokasi->getKey());
    expect($holder->condition_code)->toBe('GOOD');

    expect(AuditLog::query()
        ->where('auditable_type', DocumentProcessingAssignment::class)
        ->where('auditable_id', $assignment->getKey())
        ->where('action', 'returned')
        ->exists())->toBeTrue();

    // Pengembalian kedua ditolak (sudah tidak ACTIVE).
    $this->actingAs($wasolah)->post(
        route('document_processing_assignments.return', [$dokumen, $assignment]),
        ['condition_code' => 'GOOD']
    )->assertSessionHasErrors('status');
});

test('ipds dan wasolah melihat widget ruang pengolahan', function (): void {
    $this->actingAs(ipdsOperatorUji())->get('/dashboard')->assertOk()
        ->assertSee('Pemantauan Ruang Pengolahan', false)
        ->assertSee('Manifest Masuk Belum Diterima', false)
        ->assertSee('Dokumen Siap Olah', false)
        ->assertSee('Dokumen Sedang Diolah', false);

    $this->actingAs(wasolahUji())->get('/dashboard')->assertOk()
        ->assertSee('Pemantauan Ruang Pengolahan', false);
});

test('daftar manifest memprioritaskan tujuan pengolahan bagi ipds', function (): void {
    $admin = adminDokumenUji();
    [$manifestOlah] = manifestSiapTerimaUji($admin, 1);

    $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
    $ipds = WorkUnit::where('code', 'IPDS')->firstOrFail();

    $manifestLain = \App\Models\DocumentManifest::create([
        'manifest_number' => \App\Support\ManifestNumber::next(),
        'from_work_unit_id' => $ipds->getKey(),
        'to_work_unit_id' => $sosial->getKey(),
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);

    Livewire::actingAs(ipdsOperatorUji())
        ->test(DocumentManifestTable::class)
        ->assertSee($manifestOlah->manifest_number)
        ->assertDontSee($manifestLain->manifest_number);
});

test('ipds dan wasolah diblokir dari modul admin dan audit', function (): void {
    foreach ([ipdsOperatorUji(), wasolahUji()] as $user) {
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/roles')->assertForbidden();
        $this->actingAs($user)->get('/audit-logs')->assertForbidden();
    }
});
