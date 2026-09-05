<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Document;
use App\Models\DocumentHolder;
use App\Models\DocumentProcessingAssignment;
use App\Models\Officer;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignDocumentToProcessingOfficer
{
    /**
     * @throws ValidationException
     */
    public function handle(
        Document $document,
        Officer $officer,
        ?int $locationId,
        string $condition,
        ?string $note,
        User $actor
    ): DocumentProcessingAssignment {
        return DB::transaction(function () use ($document, $officer, $locationId, $condition, $note, $actor): DocumentProcessingAssignment {
            $officer = Officer::query()->findOrFail($officer->getKey());

            $locked = Document::query()
                ->whereKey($document->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'RECEIVED') {
                throw ValidationException::withMessages([
                    'document' => 'Hanya dokumen RECEIVED yang dapat ditugaskan.',
                ]);
            }

            $holder = $locked->holder()->lockForUpdate()->first();

            if (! $holder instanceof DocumentHolder || $holder->holder_type !== 'WORK_UNIT' || $holder->workUnit?->code !== 'PENGOLAHAN_LS') {
                throw ValidationException::withMessages([
                    'document' => 'Holder aktif harus unit PENGOLAHAN_LS.',
                ]);
            }

            if ($officer->status !== 'ACTIVE' || $officer->trashed()) {
                throw ValidationException::withMessages([
                    'officer_id' => 'Petugas harus aktif dan tidak terhapus.',
                ]);
            }

            if (! in_array($officer->workUnit?->code, DocumentProcessingAssignment::PROCESSING_UNIT_CODES, true)) {
                throw ValidationException::withMessages([
                    'officer_id' => 'Petugas harus berasal dari unit PENGOLAHAN_LS atau IPDS.',
                ]);
            }

            $existing = DocumentProcessingAssignment::query()
                ->where('document_id', $locked->getKey())
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if ($existing instanceof DocumentProcessingAssignment) {
                throw ValidationException::withMessages([
                    'document' => 'Dokumen sudah memiliki penugasan aktif.',
                ]);
            }

            $now = now();

            $assignment = new DocumentProcessingAssignment([
                'document_id' => $locked->getKey(),
                'officer_id' => $officer->getKey(),
                'assigned_by' => $actor->getKey(),
                'assigned_at' => $now,
                'status' => 'ACTIVE',
                'note' => $note,
            ]);
            $assignment->save();

            $before = $holder->condition_code;
            $fromUnitId = $holder->work_unit_id;
            $fromLocationId = $holder->document_location_id;

            $holder->holder_type = 'OFFICER';
            $holder->work_unit_id = null;
            $holder->officer_id = $officer->getKey();
            $holder->document_location_id = $locationId;
            $holder->condition_code = $condition;
            $holder->assigned_by = $actor->getKey();
            $holder->assigned_at = $now;
            $holder->save();

            $locked->holderHistories()->create([
                'from_holder_type' => 'WORK_UNIT',
                'from_work_unit_id' => $fromUnitId,
                'from_document_location_id' => $fromLocationId,
                'to_holder_type' => 'OFFICER',
                'to_officer_id' => $officer->getKey(),
                'to_document_location_id' => $locationId,
                'condition_before' => $before,
                'condition_after' => $condition,
                'movement_type' => 'PROCESSING_ASSIGNED',
                'reference_type' => DocumentProcessingAssignment::class,
                'reference_id' => $assignment->getKey(),
                'moved_by' => $actor->getKey(),
                'moved_at' => $now,
            ]);

            $locked->status = 'ASSIGNED_PROCESSING';
            $locked->save();

            AuditLogger::log('assigned', $locked, ['status' => 'RECEIVED'], ['status' => 'ASSIGNED_PROCESSING'], [
                'status_before' => 'RECEIVED',
                'status_after' => 'ASSIGNED_PROCESSING',
                'officer_id' => $officer->getKey(),
            ], $actor);

            return $assignment->refresh();
        });
    }
}
