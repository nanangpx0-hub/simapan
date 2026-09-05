<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Document;
use App\Models\DocumentHolder;
use App\Models\DocumentProcessingAssignment;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnDocumentProcessingAssignment
{
    /**
     * Terima kembali dokumen yang selesai diolah: assignment ACTIVE menjadi
     * RETURNED, holder kembali ke unit PENGOLAHAN_LS dengan kondisi fisik akhir.
     *
     * @throws ValidationException
     */
    public function handle(
        DocumentProcessingAssignment $assignment,
        ?int $locationId,
        string $condition,
        User $actor
    ): DocumentProcessingAssignment {
        if (! in_array($condition, Document::CONDITIONS, true)) {
            throw ValidationException::withMessages([
                'condition_code' => 'Kondisi fisik akhir tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($assignment, $locationId, $condition, $actor): DocumentProcessingAssignment {
            $locked = DocumentProcessingAssignment::query()
                ->whereKey($assignment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya penugasan ACTIVE yang dapat dikembalikan.',
                ]);
            }

            $document = Document::query()
                ->whereKey($locked->document_id)
                ->lockForUpdate()
                ->firstOrFail();

            $holder = $document->holder()->lockForUpdate()->first();

            if (! $holder instanceof DocumentHolder || $holder->holder_type !== 'OFFICER' || (int) $holder->officer_id !== (int) $locked->officer_id) {
                throw ValidationException::withMessages([
                    'document' => 'Holder aktif harus petugas penugasan ini.',
                ]);
            }

            $unit = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();
            $now = now();

            $locked->status = 'RETURNED';
            $locked->returned_at = $now;
            $locked->save();

            $before = $holder->condition_code;

            $holder->holder_type = 'WORK_UNIT';
            $holder->work_unit_id = $unit->getKey();
            $holder->officer_id = null;
            $holder->document_location_id = $locationId;
            $holder->condition_code = $condition;
            $holder->assigned_by = $actor->getKey();
            $holder->assigned_at = $now;
            $holder->save();

            $document->holderHistories()->create([
                'from_holder_type' => 'OFFICER',
                'from_officer_id' => $locked->officer_id,
                'from_document_location_id' => null,
                'to_holder_type' => 'WORK_UNIT',
                'to_work_unit_id' => $unit->getKey(),
                'to_document_location_id' => $locationId,
                'condition_before' => $before,
                'condition_after' => $condition,
                'movement_type' => 'PROCESSING_RETURNED',
                'reference_type' => DocumentProcessingAssignment::class,
                'reference_id' => $locked->getKey(),
                'moved_by' => $actor->getKey(),
                'moved_at' => $now,
            ]);

            $document->status = 'RECEIVED';
            $document->save();

            AuditLogger::log('returned', $locked, ['status' => 'ACTIVE'], ['status' => 'RETURNED'], [
                'status_before' => 'ACTIVE',
                'status_after' => 'RETURNED',
                'condition_after' => $condition,
                'officer_id' => $locked->officer_id,
            ], $actor);

            return $locked->refresh();
        });
    }
}
