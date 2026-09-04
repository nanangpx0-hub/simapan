<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Document;
use App\Models\DocumentHolder;
use App\Models\DocumentManifest;
use App\Models\DocumentTransfer;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitDocumentManifest
{
    /** Unit penerima valid alur SOSIAL (Tim Sosial) -> unit pengolahan/IPDS. */
    public const ALLOWED_DESTINATIONS = [
        'PENGOLAHAN_LS',
        'IPDS',
    ];

    /**
     * @throws ValidationException
     */
    public function handle(DocumentManifest $manifest, User $actor): DocumentManifest
    {
        return DB::transaction(function () use ($manifest, $actor): DocumentManifest {
            $locked = DocumentManifest::query()
                ->whereKey($manifest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'DRAFT') {
                throw ValidationException::withMessages([
                    'status' => 'Manifest status '.$locked->status.' tidak dapat disubmit.',
                ]);
            }

            $fromUnit = $locked->fromUnit()->firstOrFail();
            $toUnit = $locked->toUnit()->firstOrFail();

            if ($fromUnit->code !== 'SOSIAL' || ! in_array($toUnit->code, self::ALLOWED_DESTINATIONS, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Manifest wajib dari SOSIAL ke PENGOLAHAN_LS atau IPDS.',
                ]);
            }

            $items = $locked->items()->lockForUpdate()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Manifest membutuhkan minimal satu item.',
                ]);
            }

            foreach ($items as $item) {
                $document = Document::query()->whereKey($item->document_id)->lockForUpdate()->firstOrFail();
                $holder = $document->holder()->first();

                if ($document->status !== 'REGISTERED') {
                    throw ValidationException::withMessages([
                        'status' => 'Dokumen '.$document->id.' tidak berstatus REGISTERED.',
                    ]);
                }

                if (! $holder instanceof DocumentHolder || $holder->holder_type !== 'WORK_UNIT' || (int) $holder->work_unit_id !== (int) $locked->from_work_unit_id) {
                    throw ValidationException::withMessages([
                        'status' => 'Dokumen '.$document->id.' tidak berada pada unit pengirim.',
                    ]);
                }
            }

            $locked->status = 'SUBMITTED';
            $locked->submitted_by = $actor->getKey();
            $locked->submitted_at = now();
            $locked->save();

            AuditLogger::log('submitted', $locked, ['status' => 'DRAFT'], ['status' => 'SUBMITTED'], [
                'status_before' => 'DRAFT',
                'status_after' => 'SUBMITTED',
                'manifest_number' => $locked->manifest_number,
            ], $actor);

            $transfer = DocumentTransfer::create([
                'document_manifest_id' => $locked->getKey(),
                'transfer_status' => 'PENDING',
                'created_by' => $actor->getKey(),
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'document_manifest_item_id' => $item->getKey(),
                    'qty_sent' => $item->qty_sent,
                    'qty_received' => 0,
                    'condition_received' => null,
                    'receipt_status' => 'NOT_RECEIVED',
                    'receipt_note' => null,
                ]);

                $document = Document::query()->whereKey($item->document_id)->firstOrFail();
                $document->status = 'IN_TRANSIT';
                $document->save();

                AuditLogger::log('submitted', $document, ['status' => 'REGISTERED'], ['status' => 'IN_TRANSIT'], [
                    'status_before' => 'REGISTERED',
                    'status_after' => 'IN_TRANSIT',
                    'manifest_number' => $locked->manifest_number,
                ], $actor);

                $document->holderHistories()->create([
                    'from_holder_type' => 'WORK_UNIT',
                    'from_work_unit_id' => $locked->from_work_unit_id,
                    'to_holder_type' => 'WORK_UNIT',
                    'to_work_unit_id' => $locked->from_work_unit_id,
                    'condition_before' => $item->condition_sent,
                    'condition_after' => $item->condition_sent,
                    'movement_type' => 'MANIFEST_SUBMITTED',
                    'reference_type' => DocumentManifest::class,
                    'reference_id' => $locked->getKey(),
                    'moved_by' => $actor->getKey(),
                    'moved_at' => now(),
                ]);
            }

            return $locked->refresh();
        });
    }
}
