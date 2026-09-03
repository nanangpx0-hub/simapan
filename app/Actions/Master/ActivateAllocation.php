<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Allocation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateAllocation
{
    /**
     * @throws ValidationException
     */
    public function handle(Allocation $allocation, User $actor): Allocation
    {
        return DB::transaction(function () use ($allocation, $actor): Allocation {
            $locked = Allocation::query()
                ->whereKey($allocation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->canTransitionTo('ACTIVE')) {
                throw ValidationException::withMessages([
                    'status' => 'Alokasi status '.$locked->status.' tidak dapat diaktifkan.',
                ]);
            }

            $period = $locked->period()->firstOrFail();

            if ($period->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'status' => 'Periode survei harus ACTIVE untuk mengaktifkan alokasi.',
                ]);
            }

            $village = $locked->village()->firstOrFail();

            if (! $village->is_active || $village->level !== 'DESA_KELURAHAN_NAGARI') {
                throw ValidationException::withMessages([
                    'status' => 'Desa wilayah harus aktif dan berlevel desa/kelurahan.',
                ]);
            }

            $missing = $locked->missingAssignmentRoles();

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Penugasan belum lengkap: '.implode(', ', $missing).'.',
                ]);
            }

            $locked->status = 'ACTIVE';
            $locked->save();

            AuditLogger::log('activated', $locked, ['status' => 'DRAFT'], ['status' => 'ACTIVE'], [
                'status_before' => 'DRAFT',
                'status_after' => 'ACTIVE',
            ], $actor);

            return $locked->refresh();
        });
    }
}
