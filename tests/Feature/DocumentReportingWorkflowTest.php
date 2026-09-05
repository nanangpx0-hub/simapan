<?php

declare(strict_types=1);

use App\Exports\ReportingReconciliationExport;
use App\Models\Allocation;
use App\Models\DocumentType;
use App\Models\Officer;
use App\Models\ProcessingEntryReport;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use App\Services\DocumentReconciliationService;
use App\Services\ProcessingEntryReportService;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        DocumentTypeSeeder::class,
    ]);

    $this->service = app(ProcessingEntryReportService::class);

    $this->actor = User::factory()->create();
    $this->actor->assignRole('viewer');

    DB::beginTransaction();
});

afterEach(function (): void {
    DB::rollBack();
});

/**
 * Buat periode survei beserta SLA di masa depan.
 */
function makePeriod(?string $sampelEntryDeadline = null): SurveyPeriod
{
    /** @var SurveyType $type */
    $type = SurveyType::firstOrCreate(
        ['code' => 'SUSENAS'],
        ['name' => 'Survey Susenas', 'is_active' => true]
    );

    $period = SurveyPeriod::create([
        'code' => 'PER-'.strtoupper(fake()->unique()->bothify('####')),
        'survey_type_id' => $type->getKey(),
        'name' => 'Periode Uji',
        'period_type' => 'TAHUNAN',
        'period_number' => null,
        'year' => (int) date('Y'),
        'start_date' => now()->subMonth()->toDateString(),
        'end_date' => now()->addMonths(2)->toDateString(),
        'pemutakhiran_submission_deadline' => now()->addDays(5)->toDateString(),
        'pemutakhiran_entry_deadline' => now()->addDays(20)->toDateString(),
        'sampel_submission_deadline' => now()->addDays(10)->toDateString(),
        'sampel_entry_deadline' => $sampelEntryDeadline ?? now()->addDays(30)->toDateString(),
        'status' => 'ACTIVE',
        'created_by' => User::factory()->create()->getKey(),
    ]);

    return $period;
}

/**
 * Buat alokasi (NKS) dalam periode.
 */
function makeAllocation(SurveyPeriod $period): Allocation
{
    $createdBy = User::factory()->create()->getKey();

    $provinsi = Region::firstOrCreate(
        ['full_code' => 'RND-P'],
        ['parent_id' => null, 'level' => 'PROVINSI', 'code' => 'RND-P', 'name' => 'Prov Uji', 'is_active' => true]
    );
    $kabupaten = Region::firstOrCreate(
        ['full_code' => 'RND-PRND-K'],
        ['parent_id' => $provinsi->getKey(), 'level' => 'KAB_KOTA', 'code' => 'RND-K', 'name' => 'Kab Uji', 'is_active' => true]
    );
    $kecamatan = Region::firstOrCreate(
        ['full_code' => 'RND-PRND-KRND-C'],
        ['parent_id' => $kabupaten->getKey(), 'level' => 'KECAMATAN', 'code' => 'RND-C', 'name' => 'Kec Uji', 'is_active' => true]
    );
    $desa = Region::firstOrCreate(
        ['full_code' => 'RND-PRND-KRND-CRND-D'],
        ['parent_id' => $kecamatan->getKey(), 'level' => 'DESA_KELURAHAN_NAGARI', 'code' => 'RND-D', 'name' => 'Desa Uji', 'is_active' => true]
    );

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => 'NKS-UJI-'.fake()->unique()->numerify('####'),
        'sls_code' => 'SLS-01',
        'sub_sls_code' => '0',
        'sls_name' => 'SLS Uji',
        'status' => 'ACTIVE',
        'created_by' => $createdBy,
    ]);
}

/**
 * Ambil DocumentType berdasarkan kode.
 */
function docType(string $code): DocumentType
{
    return DocumentType::where('code', $code)->firstOrFail();
}
test('kelima jenis laporan dapat dibuat dengan format valid', function (): void {
    $period = makePeriod();
    $allocation = makeAllocation($period);
    $officer = Officer::factory()->create(['status' => 'ACTIVE']);

    // Lap1 = serah pemutakhiran (P_SUSENAS)
    $lap1 = $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('P_SUSENAS')->getKey(),
        'report_type' => 'PEMUTAKHIRAN_SUSENAS',
        'batch_number' => 'BATCH-01',
        'officer_id' => $officer->getKey(),
    ]);

    expect($lap1->report_type)->toBe('PEMUTAKHIRAN_SUSENAS')
        ->and($lap1->entry_status)->toBe('PENDING')
        ->and($lap1->target_qty)->toBe(1);

    // Lap4 = entri sampel SUSENAS (VSEN_SUSENAS), target 10 ruta
    $lap4 = $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('VSEN_SUSENAS')->getKey(),
        'report_type' => 'SAMPEL_SUSENAS',
        'officer_id' => $officer->getKey(),
    ]);

    expect($lap4->target_qty)->toBe(ProcessingEntryReport::SAMPEL_RUTA_PER_NKS);
});

test('laporan dengan tipe dokumen yang salah ditolak', function (): void {
    $period = makePeriod();
    $allocation = makeAllocation($period);

    // VSEN_SUSENAS tidak sesuai untuk PEMUTAKHIRAN_SUSENAS
    expect(fn () => $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('VSEN_SUSENAS')->getKey(),
        'report_type' => 'PEMUTAKHIRAN_SUSENAS',
    ]))->toThrow(ValidationException::class);
});

test('entri seruti ditolak bila sampel susenas belum ada', function (): void {
    $period = makePeriod();
    $allocation = makeAllocation($period);

    // Lap5 (SAMPEL_SERUTI) tanpa Lap4 (SAMPEL_SUSENAS) -> ditolak.
    expect(fn () => $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('VSERUTI')->getKey(),
        'report_type' => 'SAMPEL_SERUTI',
    ]))->toThrow(ValidationException::class);
});

test('entri seruti dizinkan setelah sampel susenas selesai dientri bersih', function (): void {
    $period = makePeriod();
    $allocation = makeAllocation($period);
    $officer = Officer::factory()->create(['status' => 'ACTIVE']);

    $lap4 = $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('VSEN_SUSENAS')->getKey(),
        'report_type' => 'SAMPEL_SUSENAS',
        'officer_id' => $officer->getKey(),
    ]);

    $lap4 = $this->service->updateProgress($lap4, [
        'processed_qty' => 10,
        'clean_qty' => 10,
        'error_qty' => 0,
        'entry_status' => 'COMPLETED',
    ]);

    expect($lap4->entry_status)->toBe('COMPLETED');

    $lap5 = $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('VSERUTI')->getKey(),
        'report_type' => 'SAMPEL_SERUTI',
        'parent_entry_report_id' => $lap4->getKey(),
        'officer_id' => $officer->getKey(),
    ]);

    expect($lap5->report_type)->toBe('SAMPEL_SERUTI')
        ->and($lap5->parent_entry_report_id)->toBe($lap4->getKey());
});
test('status SLA sesuai deadline periode (On Track vs Overdue)', function (): void {
    // Deadline masa lalu -> OVERDUE
    $overduePeriod = makePeriod(now()->subDay()->toDateString());
    $overdueAlloc = makeAllocation($overduePeriod);
    $officer = Officer::factory()->create(['status' => 'ACTIVE']);

    $ov = $this->service->create([
        'survey_period_id' => $overduePeriod->getKey(),
        'allocation_id' => $overdueAlloc->getKey(),
        'document_type_id' => docType('VSEN_SUSENAS')->getKey(),
        'report_type' => 'SAMPEL_SUSENAS',
        'officer_id' => $officer->getKey(),
    ]);

    expect($ov->isOverdue())->toBeTrue()
        ->and($ov->slaStatus())->toBe('OVERDUE')
        ->and($ov->daysRemaining())->toBeLessThan(0);

    // Deadline masa depan jauh -> ON_TRACK
    $trackPeriod = makePeriod(now()->addDays(10)->toDateString());
    $trackAlloc = makeAllocation($trackPeriod);

    $tr = $this->service->create([
        'survey_period_id' => $trackPeriod->getKey(),
        'allocation_id' => $trackAlloc->getKey(),
        'document_type_id' => docType('VSEN_SUSENAS')->getKey(),
        'report_type' => 'SAMPEL_SUSENAS',
        'officer_id' => $officer->getKey(),
    ]);

    expect($tr->isOverdue())->toBeFalse()
        ->and($tr->slaStatus())->toBe('ON_TRACK');
});

test('ekspor excel laporan rekonsiliasi berhasil', function (): void {
    $period = makePeriod();
    makeAllocation($period);

    $exporter = new ReportingReconciliationExport([
        ['nks' => 'NKS-UJI-0001', 'lap1' => 'BELUM', 'lap3' => 'PENDING', 'lap2' => 'BELUM', 'lap4' => 'PENDING', 'lap5' => 'PENDING', 'recon' => 'PENDING', 'sla' => 'ON_TRACK'],
    ], 'Periode Uji');

    $filename = 'rekonsiliasi-'.Str::uuid().'.xlsx';
    Excel::store($exporter, $filename);

    expect(Storage::disk('local')->exists($filename))->toBeTrue();

    Storage::disk('local')->delete($filename);
});
test('rekonsiliasi mendeteksi selisih NKS belum dientri (DISCREPANCY)', function (): void {
    $period = makePeriod();
    $allocation = makeAllocation($period);

    // Lap1 dibuat, namun belum ada entri pemutakhiran (Lap3) selesai.
    $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('P_SUSENAS')->getKey(),
        'report_type' => 'PEMUTAKHIRAN_SUSENAS',
    ]);

    $result = app(DocumentReconciliationService::class)->reconcileSubmissionVsEntry($period);

    expect($result['discrepancy'])->toBe(1);

    $report = ProcessingEntryReport::where('allocation_id', $allocation->getKey())
        ->where('report_type', 'PEMUTAKHIRAN_SUSENAS')
        ->firstOrFail();

    expect($report->entry_status)->toBe('DISCREPANCY')
        ->and($report->reconciliation_note)->toContain($allocation->nks);
});

test('rekonsiliasi menandai RECONCILED bila seluruh entri lengkap', function (): void {
    $period = makePeriod();
    $allocation = makeAllocation($period);
    $officer = Officer::factory()->create(['status' => 'ACTIVE']);

    $lap1 = $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('P_SUSENAS')->getKey(),
        'report_type' => 'PEMUTAKHIRAN_SUSENAS',
        'officer_id' => $officer->getKey(),
    ]);

    $this->service->updateProgress($lap1, ['processed_qty' => 1, 'clean_qty' => 1, 'error_qty' => 0, 'entry_status' => 'COMPLETED']);

    $lap4 = $this->service->create([
        'survey_period_id' => $period->getKey(),
        'allocation_id' => $allocation->getKey(),
        'document_type_id' => docType('VSEN_SUSENAS')->getKey(),
        'report_type' => 'SAMPEL_SUSENAS',
        'officer_id' => $officer->getKey(),
    ]);

    $this->service->updateProgress($lap4, ['processed_qty' => 10, 'clean_qty' => 10, 'error_qty' => 0, 'entry_status' => 'COMPLETED']);

    $result = app(DocumentReconciliationService::class)->reconcileSubmissionVsEntry($period);

    expect($result['reconciled'])->toBe(1)
        ->and(ProcessingEntryReport::where('allocation_id', $allocation->getKey())->where('entry_status', 'DISCREPANCY')->exists())->toBeFalse();
});
