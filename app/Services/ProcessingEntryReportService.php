<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Allocation;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\ProcessingEntryReport;
use App\Models\SurveyPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Layanan pembuatan dan pemutakhiran laporan entri pengolahan
 * (5 laporan SUSENAS-SERUTI) dengan standarisasi dan validasi linkage.
 */
final class ProcessingEntryReportService
{
    /**
     * Buat laporan entri baru dengan validasi standar.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProcessingEntryReport
    {
        $validated = $this->validateBase($data);

        $allocation = Allocation::query()->findOrFail((int) $validated['allocation_id']);
        $period = SurveyPeriod::query()->findOrFail((int) $validated['survey_period_id']);

        if ((int) $allocation->survey_period_id !== (int) $period->getKey()) {
            throw ValidationException::withMessages([
                'allocation_id' => 'NKS tidak termasuk dalam periode survei yang dipilih.',
            ]);
        }

        $existing = ProcessingEntryReport::query()
            ->where('allocation_id', $allocation->getKey())
            ->where('report_type', $validated['report_type'])
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'report_type' => 'Laporan jenis ini sudah ada untuk NKS tersebut.',
            ]);
        }

        $this->validateDocumentType($validated);

        $document = $this->resolveDocument($validated, $allocation);
        $parent = $this->resolveParent($validated, (int) $allocation->getKey());

        return DB::transaction(function () use ($validated, $document, $parent): ProcessingEntryReport {
            /** @var ProcessingEntryReport $report */
            $report = ProcessingEntryReport::create([
                ...$validated,
                'document_id' => $document?->getKey(),
                'target_qty' => $validated['target_qty'] ?? $this->defaultTarget($validated['report_type']),
                'processed_qty' => 0,
                'clean_qty' => 0,
                'error_qty' => 0,
                'entry_status' => 'PENDING',
                'assigned_at' => ! empty($validated['officer_id']) ? now() : null,
                'parent_entry_report_id' => $parent?->getKey(),
            ]);

            return $report;
        });
    }

    /**
     * Mulai entri (PENDING -> IN_PROGRESS) dengan petugas.
     */
    public function start(ProcessingEntryReport $report, int $officerId): ProcessingEntryReport
    {
        if ($report->entry_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'entry_status' => 'Hanya laporan berstatus PENDING yang dapat dimulai.',
            ]);
        }

        $report->update([
            'officer_id' => $officerId,
            'assigned_at' => $report->assigned_at ?? now(),
            'started_at' => now(),
            'entry_status' => 'IN_PROGRESS',
        ]);

        return $report->refresh();
    }

    /**
     * Perbarui progres entri. COMPLETED mensyaratkan processed >= target.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProgress(ProcessingEntryReport $report, array $data): ProcessingEntryReport
    {
        if (! in_array($report->entry_status, ['PENDING', 'IN_PROGRESS'], true)) {
            throw ValidationException::withMessages([
                'entry_status' => 'Laporan sudah final (COMPLETED/RECONCILED/DISCREPANCY).',
            ]);
        }

        $processed = (int) ($data['processed_qty'] ?? $report->processed_qty);
        $clean = (int) ($data['clean_qty'] ?? $report->clean_qty);
        $error = (int) ($data['error_qty'] ?? $report->error_qty);

        if ($clean + $error !== $processed) {
            throw ValidationException::withMessages([
                'processed_qty' => 'clean_qty + error_qty harus sama dengan processed_qty.',
            ]);
        }

        $status = $data['entry_status'] ?? $report->entry_status;

        if ($status === 'COMPLETED' && $report->target_qty > 0 && $processed < $report->target_qty) {
            throw ValidationException::withMessages([
                'processed_qty' => 'Belum memenuhi target; kurang '.($report->target_qty - $processed).' ruta/berkas.',
            ]);
        }

        $report->update([
            'processed_qty' => $processed,
            'clean_qty' => $clean,
            'error_qty' => $error,
            'started_at' => $report->started_at ?? now(),
            'completed_at' => $status === 'COMPLETED' ? now() : $report->completed_at,
            'entry_status' => $status,
        ]);

        return $report->refresh();
    }

    /**
     * Validasi linkage: SAMPEL_SERUTI wajib memiliki laporan SAMPEL_SUSENAS
     * induk pada NKS yang sama dengan status bersih (COMPLETED/RECONCILED).
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveParent(array $data, int $allocationId): ?ProcessingEntryReport
    {
        if ($data['report_type'] !== 'SAMPEL_SERUTI') {
            return null;
        }

        if (! empty($data['parent_entry_report_id'])) {
            $parent = ProcessingEntryReport::query()->findOrFail((int) $data['parent_entry_report_id']);
        } else {
            $parent = ProcessingEntryReport::query()
                ->where('allocation_id', $allocationId)
                ->where('report_type', 'SAMPEL_SUSENAS')
                ->first();
        }

        if (! $parent instanceof ProcessingEntryReport) {
            throw ValidationException::withMessages([
                'parent_entry_report_id' => 'Laporan sampel SUSENAS (Laporan 4) untuk NKS ini belum ada; entri SERUTI ditolak.',
            ]);
        }

        if ($parent->report_type !== 'SAMPEL_SUSENAS') {
            throw ValidationException::withMessages([
                'parent_entry_report_id' => 'Laporan induk Seruti harus bertipe SAMPEL_SUSENAS.',
            ]);
        }

        if (! in_array($parent->entry_status, ProcessingEntryReport::CLEAN_STATUSES, true)) {
            throw ValidationException::withMessages([
                'parent_entry_report_id' => 'Sampel SUSENAS induk belum selesai dientri dengan status bersih.',
            ]);
        }

        return $parent;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateDocumentType(array $validated): void
    {
        $documentType = DocumentType::query()->findOrFail((int) $validated['document_type_id']);
        $expectedCode = ProcessingEntryReport::DOCUMENT_TYPE_CODES[$validated['report_type']];

        if ($documentType->code !== $expectedCode) {
            throw ValidationException::withMessages([
                'document_type_id' => "Tipe dokumen untuk {$validated['report_type']} harus berkode {$expectedCode}.",
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveDocument(array $validated, Allocation $allocation): ?Document
    {
        if (empty($validated['document_id'])) {
            return null;
        }

        /** @var Document|null $document */
        $document = Document::query()->find($validated['document_id']);

        if (! $document instanceof Document) {
            throw ValidationException::withMessages([
                'document_id' => 'Dokumen tidak ditemukan.',
            ]);
        }

        if ((int) $document->allocation_id !== (int) $allocation->getKey()) {
            throw ValidationException::withMessages([
                'document_id' => 'Dokumen tidak terkait dengan NKS yang dipilih.',
            ]);
        }

        return $document;
    }

    private function defaultTarget(string $reportType): int
    {
        return $reportType === 'PEMUTAKHIRAN_SUSENAS' ? 1 : ProcessingEntryReport::SAMPEL_RUTA_PER_NKS;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateBase(array $data): array
    {
        $validator = validator($data, [
            'survey_period_id' => ['required', 'integer', 'exists:survey_periods,id'],
            'allocation_id' => ['required', 'integer', 'exists:allocations,id'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'report_type' => ['required', 'string', 'in:'.implode(',', ProcessingEntryReport::REPORT_TYPES)],
            'batch_number' => ['nullable', 'string', 'max:64'],
            'officer_id' => ['nullable', 'integer', 'exists:officers,id'],
            'target_qty' => ['sometimes', 'integer', 'min:0'],
            'parent_entry_report_id' => ['nullable', 'integer'],
        ]);

        return $validator->validate();
    }
}
