<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Allocation;
use App\Models\DocumentManifest;
use App\Models\UpdatingManifestItem;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\AuditLogger;
use App\Support\ManifestNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUpdatingDocumentManifest
{
    /**
     * Buat manifest penyerahan pemutakhiran (BAST-P-SUSENAS) dari batch alokasi.
     *
     * @param  array<int, array{allocation_id: int, household_count_listing: int, has_vsen_p?: bool, has_peta_ws?: bool}>  $rows
     *
     * @throws ValidationException
     */
    public function handle(array $rows, User $actor): DocumentManifest
    {
        if ($rows === []) {
            throw ValidationException::withMessages([
                'allocations' => 'Pilih minimal satu alokasi NKS pemutakhiran.',
            ]);
        }

        return DB::transaction(function () use ($rows, $actor): DocumentManifest {
            $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();
            $olah = WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail();

            $manifest = new DocumentManifest([
                'manifest_number' => ManifestNumber::nextUpdating(),
                'from_work_unit_id' => $sosial->getKey(),
                'to_work_unit_id' => $olah->getKey(),
                'status' => 'SUBMITTED',
                'submitted_by' => $actor->getKey(),
                'submitted_at' => now(),
                'created_by' => $actor->getKey(),
            ]);
            $manifest->save();

            $seen = [];

            foreach ($rows as $index => $row) {
                $allocationId = (int) ($row['allocation_id'] ?? 0);
                $households = (int) ($row['household_count_listing'] ?? -1);

                if (isset($seen[$allocationId])) {
                    throw ValidationException::withMessages([
                        "rows.{$index}.allocation_id" => 'Alokasi duplikat dalam satu manifest.',
                    ]);
                }
                $seen[$allocationId] = true;

                $allocation = Allocation::query()
                    ->with(['village.parent'])
                    ->find($allocationId);

                if (! $allocation instanceof Allocation) {
                    throw ValidationException::withMessages([
                        "rows.{$index}.allocation_id" => 'Alokasi tidak ditemukan.',
                    ]);
                }

                if ($households < 0) {
                    throw ValidationException::withMessages([
                        "rows.{$index}.household_count_listing" => 'Jumlah rumah tangga listing wajib diisi.',
                    ]);
                }

                $duplicate = UpdatingManifestItem::query()
                    ->where('allocation_id', $allocation->getKey())
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        "rows.{$index}.allocation_id" => 'Alokasi sudah terdaftar pada manifest pemutakhiran.',
                    ]);
                }

                $village = $allocation->village;

                $manifest->updatingItems()->create([
                    'allocation_id' => $allocation->getKey(),
                    // NKS selalu string: leading-zero terjaga.
                    'nks' => (string) $allocation->nks,
                    'kecamatan_code' => (string) ($village?->parent?->full_code ?? $village?->full_code ?? '—'),
                    'desa_code' => (string) ($village?->full_code ?? '—'),
                    'household_count_listing' => $households,
                    'has_vsen_p' => (bool) ($row['has_vsen_p'] ?? true),
                    'has_peta_ws' => (bool) ($row['has_peta_ws'] ?? true),
                    'physical_condition' => 'GOOD',
                    'delivery_status' => 'SENT_BY_SOCIAL',
                ]);
            }

            AuditLogger::log('submitted', $manifest, ['status' => 'DRAFT'], ['status' => 'SUBMITTED'], [
                'status_before' => 'DRAFT',
                'status_after' => 'SUBMITTED',
                'manifest_number' => $manifest->manifest_number,
                'nks_count' => count($rows),
            ], $actor);

            return $manifest->refresh();
        });
    }
}
