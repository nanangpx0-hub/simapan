<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Document;
use App\Models\DocumentManifest;
use App\Models\DocumentTransferItem;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveDocumentManifest
{
    /**
     * @param  array<int, array{qty_received: int, condition_received: ?string, receipt_status: string, receipt_note: ?string}>  $items
     *
     * @throws ValidationException
     */
    public function handle(
        DocumentManifest $manifest,
        array $items,
        ?int $locationId,
        ?string $receiptNote,
        User $actor
    ): DocumentManifest {
        return DB::transaction(function () use ($manifest, $items, $locationId, $receiptNote, $actor): DocumentManifest {
            $locked = DocumentManifest::query()
                ->whereKey($manifest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'SUBMITTED') {
                throw ValidationException::withMessages([
                    'status' => 'Manifest status '.$locked->status.' tidak dapat diterima.',
                ]);
            }

            $transfer = $locked->transfer()->lockForUpdate()->firstOrFail();
            $toUnitId = (int) $locked->to_work_unit_id;

            $outcomes = [];

            foreach ($transfer->items()->lockForUpdate()->get() as $transferItem) {
                $payload = $items[$transferItem->getKey()] ?? null;

                if (! is_array($payload)) {
                    throw ValidationException::withMessages([
                        'items' => 'Data penerimaan tiap item wajib lengkap.',
                    ]);
                }

                $qtyReceived = (int) ($payload['qty_received'] ?? 0);
                $condition = $payload['condition_received'] ?? null;
                $status = (string) ($payload['receipt_status'] ?? '');
                $note = $payload['receipt_note'] ?? null;

                if (! in_array($status, DocumentTransferItem::RECEIPT_STATUSES, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Status penerimaan item tidak valid.',
                    ]);
                }

                if ($qtyReceived < 0 || $qtyReceived > (int) $transferItem->qty_sent) {
                    throw ValidationException::withMessages([
                        'items' => 'Qty diterima harus 0 sampai qty dikirim.',
                    ]);
                }

                if ($condition !== null && ! in_array($condition, Document::CONDITIONS, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Kondisi penerimaan tidak valid.',
                    ]);
                }

                if ($status !== 'COMPLETE' && trim((string) ($note ?? '')) === '') {
                    throw ValidationException::withMessages([
                        'items' => 'Catatan wajib bila status item bukan COMPLETE.',
                    ]);
                }

                if ($condition === null || trim((string) $condition) === '') {
                    throw ValidationException::withMessages([
                        'items' => 'Kondisi penerimaan wajib diisi.',
                    ]);
                }

                $transferItem->qty_received = $qtyReceived;
                $transferItem->condition_received = $condition;
                $transferItem->receipt_status = $status;
                $transferItem->receipt_note = $note;
                $transferItem->save();

                $manifestItem = $transferItem->manifestItem()->firstOrFail();
                $document = Document::query()->whereKey($manifestItem->document_id)->lockForUpdate()->firstOrFail();

                $outcomes[$document->getKey()] = $this->resolveDocument($document, $qtyReceived, (int) $manifestItem->qty_sent, $status, $condition, $toUnitId, $locationId, $actor, $locked);
            }

            $result = $this->resolveManifestResult($transfer->items()->get());

            if ($result === 'REJECTED' && trim((string) ($receiptNote ?? '')) === '') {
                throw ValidationException::withMessages([
                    'receipt_note' => 'Catatan manifest wajib bila seluruh manifest ditolak.',
                ]);
            }

            $manifestStatus = match ($result) {
                'COMPLETE' => 'RECEIVED_COMPLETE',
                'NOTED' => 'RECEIVED_NOTED',
                'PARTIAL' => 'RECEIVED_PARTIAL',
                default => 'REJECTED',
            };

            $locked->status = $manifestStatus;
            $locked->received_by = $actor->getKey();
            $locked->received_at = now();
            $locked->save();

            $manifestAction = match ($result) {
                'COMPLETE' => 'received_complete',
                'NOTED' => 'received_noted',
                'PARTIAL' => 'received_partial',
                default => 'rejected',
            };

            AuditLogger::log($manifestAction, $locked, ['status' => 'SUBMITTED'], ['status' => $manifestStatus], [
                'status_before' => 'SUBMITTED',
                'status_after' => $manifestStatus,
                'manifest_number' => $locked->manifest_number,
                'receipt_result' => $result,
            ], $actor);

            $transfer->transfer_status = $result === 'REJECTED' ? 'REJECTED' : 'RECEIVED';
            $transfer->received_by = $actor->getKey();
            $transfer->received_at = now();
            $transfer->checked_by = $actor->getKey();
            $transfer->checked_at = now();
            $transfer->receipt_result = $result;
            $transfer->receipt_note = $receiptNote;
            $transfer->save();

            return $locked->refresh();
        });
    }

    private function resolveDocument(
        Document $document,
        int $qtyReceived,
        int $qtySent,
        string $status,
        ?string $condition,
        int $toUnitId,
        ?int $locationId,
        User $actor,
        DocumentManifest $manifest
    ): string {
        if ($status === 'REJECTED' || $qtyReceived === 0) {
            $document->status = $status === 'REJECTED' ? 'REGISTERED' : 'IN_TRANSIT';
            $document->save();

            AuditLogger::log($status === 'REJECTED' ? 'rejected' : 'received_partial', $document, [], ['status' => $document->status], [
                'manifest_number' => $manifest->manifest_number,
            ], $actor);

            return 'REJECTED';
        }

        $isFull = $qtyReceived === $qtySent;
        $isClean = $condition === 'GOOD';

        $document->status = $isFull ? 'RECEIVED' : 'RECEIVED_PARTIAL';
        $document->save();

        $holder = $document->holder()->firstOrFail();
        $before = $holder->condition_code;

        $holder->holder_type = 'WORK_UNIT';
        $holder->work_unit_id = $toUnitId;
        $holder->officer_id = null;
        $holder->document_location_id = $locationId;
        $holder->condition_code = $condition ?? 'NOT_APPLICABLE';
        $holder->assigned_by = $actor->getKey();
        $holder->assigned_at = now();
        $holder->save();

        $document->holderHistories()->create([
            'from_holder_type' => 'WORK_UNIT',
            'from_work_unit_id' => $manifest->from_work_unit_id,
            'to_holder_type' => 'WORK_UNIT',
            'to_work_unit_id' => $toUnitId,
            'to_document_location_id' => $locationId,
            'condition_before' => $before,
            'condition_after' => $condition ?? 'NOT_APPLICABLE',
            'movement_type' => 'MANIFEST_RECEIVED',
            'reference_type' => DocumentManifest::class,
            'reference_id' => $manifest->getKey(),
            'moved_by' => $actor->getKey(),
            'moved_at' => now(),
        ]);

        $action = 'received_complete';

        if (! $isFull) {
            $action = 'received_partial';
        } elseif (! $isClean) {
            $action = 'received_noted';
        }

        AuditLogger::log($action, $document, [], ['status' => $document->status], [
            'manifest_number' => $manifest->manifest_number,
        ], $actor);

        return $isFull ? ($isClean ? 'COMPLETE' : 'NOTED') : 'PARTIAL';
    }

    /**
     * @param  Collection<int, DocumentTransferItem>  $items
     */
    private function resolveManifestResult($items): string
    {
        $statuses = $items->pluck('receipt_status')->all();

        if ($statuses !== [] && count(array_filter($statuses, fn ($status): bool => $status === 'REJECTED')) === count($statuses)) {
            return 'REJECTED';
        }

        $hasShortfall = $items->contains(fn ($item): bool => (int) $item->qty_received < (int) $item->qty_sent);

        if ($hasShortfall) {
            return 'PARTIAL';
        }

        $hasNote = $items->contains(fn ($item): bool => trim((string) ($item->receipt_note ?? '')) !== '' || ($item->condition_received ?? 'GOOD') !== 'GOOD');

        return $hasNote ? 'NOTED' : 'COMPLETE';
    }
}
