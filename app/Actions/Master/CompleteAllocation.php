<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Allocation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteAllocation
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

            if (! $locked->canTransitionTo('COMPLETED')) {
                throw ValidationException::withMessages([
                    'status' => 'Alokasi status '.$locked->status.' tidak dapat diselesaikan.',
                ]);
            }

            $missing = $locked->missingAssignmentRoles();

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Penugasan belum lengkap: '.implode(', ', $missing).'.',
                ]);
            }

            $locked->status = 'COMPLETED';
            $locked->save();

            AuditLogger::log('completed', $locked, ['status' => 'ACTIVE'], ['status' => 'COMPLETED'], [
                'status_before' => 'ACTIVE',
                'status_after' => 'COMPLETED',
            ], $actor);

            return $locked->refresh();
        });
    }
}
