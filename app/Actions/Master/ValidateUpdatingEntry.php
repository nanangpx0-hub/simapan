<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\DocumentType;
use App\Models\ProcessingEntryReport;
use App\Models\UpdatingManifestItem;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ValidateUpdatingEntry
{
    /**
     * Verifikasi COUNT entri terhadap household_count_listing. Selisih dicatat
     * sebagai DISCREPANCY pada ProcessingEntryReport; hasil sinkron COMPLETED.
     *
     * @throws ValidationException
     */
    public function handle(UpdatingManifestItem $item, int $entryCount, User $actor): ProcessingEntryReport
    {
        if ($entryCount < 0) {
            throw ValidationException::withMessages([
                'entry_count' => 'Jumlah entri tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($item, $entryCount, $actor): ProcessingEntryReport {
            $locked = UpdatingManifestItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $allocation = $locked->allocation()->firstOrFail();
            $target = (int) $locked->household_count_listing;
            $match = $entryCount === $target;

            $type = DocumentType::firstOrCreate(
                ['code' => 'P_SUSENAS'],
                ['name' => 'Dokumen Pemutakhiran Susenas', 'is_active' => true]
            );

            $report = ProcessingEntryReport::firstOrCreate(
                [
                    'allocation_id' => $allocation->getKey(),
                    'report_type' => 'PEMUTAKHIRAN_SUSENAS',
                ],
                [
                    'survey_period_id' => $allocation->survey_period_id,
                    'document_type_id' => $type->getKey(),
                    'officer_id' => $locked->processing_officer_id,
                    'target_qty' => $target,
                    'entry_status' => 'PENDING',
                ]
            );

            $report->target_qty = $target;
            $report->processed_qty = $entryCount;
            $report->officer_id = $locked->processing_officer_id;
            $report->entry_status = $match ? 'COMPLETED' : 'DISCREPANCY';
            $report->reconciliation_note = $match ? null : "Selisih entri: listing {$target} ruta, dientri {$entryCount} ruta (NKS {$locked->nks}).";
            $report->save();

            AuditLogger::log('reconciled', $report, [], ['entry_status' => $report->entry_status], [
                'nks' => $locked->nks,
                'target_qty' => $target,
                'processed_qty' => $entryCount,
            ], $actor);

            return $report->refresh();
        });
    }
}
