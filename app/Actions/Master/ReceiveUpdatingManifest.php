<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\DocumentManifest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveUpdatingManifest
{
    /**
     * Terima fisik berkas pemutakhiran dengan checklist ganda VSEN26.P + Peta WS.
     *
     * @param  array<int, array{has_vsen_p: bool, has_peta_ws: bool, receive_note?: ?string}>  $checks  keyed by item id
     *
     * @throws ValidationException
     */
    public function handle(DocumentManifest $manifest, array $checks, User $actor): DocumentManifest
    {
        return DB::transaction(function () use ($manifest, $checks, $actor): DocumentManifest {
            $locked = DocumentManifest::query()
                ->whereKey($manifest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'SUBMITTED') {
                throw ValidationException::withMessages([
                    'status' => 'Manifest status '.$locked->status.' tidak dapat diterima.',
                ]);
            }

            if ($locked->updatingItems()->count() === 0) {
                throw ValidationException::withMessages([
                    'status' => 'Manifest pemutakhiran tanpa baris NKS.',
                ]);
            }

            foreach ($locked->updatingItems()->lockForUpdate()->get() as $item) {
                $payload = $checks[$item->getKey()] ?? null;

                if (! is_array($payload)) {
                    throw ValidationException::withMessages([
                        'items' => 'Checklist serah terima tiap NKS wajib lengkap.',
                    ]);
                }

                $hasVsen = (bool) ($payload['has_vsen_p'] ?? false);
                $hasPeta = (bool) ($payload['has_peta_ws'] ?? false);
                $note = $payload['receive_note'] ?? null;
                $complete = $hasVsen && $hasPeta;

                if (! $complete && trim((string) $note) === '') {
                    throw ValidationException::withMessages([
                        "items.{$item->getKey()}" => 'Berkas tidak lengkap wajib disertai catatan serah terima.',
                    ]);
                }

                $item->has_vsen_p = $hasVsen;
                $item->has_peta_ws = $hasPeta;
                $item->physical_condition = $complete ? 'GOOD' : 'INCOMPLETE';
                $item->receive_note = $note;
                $item->delivery_status = 'RECEIVED_BY_PLS';
                $item->save();
            }

            $locked->status = 'RECEIVED_BY_PLS';
            $locked->received_by = $actor->getKey();
            $locked->received_at = now();
            $locked->save();

            AuditLogger::log('received', $locked, ['status' => 'SUBMITTED'], ['status' => 'RECEIVED_BY_PLS'], [
                'status_before' => 'SUBMITTED',
                'status_after' => 'RECEIVED_BY_PLS',
                'manifest_number' => $locked->manifest_number,
            ], $actor);

            return $locked->refresh();
        });
    }
}
