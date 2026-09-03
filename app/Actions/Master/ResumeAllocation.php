<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Allocation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResumeAllocation
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
                    'status' => 'Alokasi status '.$locked->status.' tidak dapat dilanjutkan.',
                ]);
            }

            $locked->status = 'ACTIVE';
            $locked->save();

            AuditLogger::log('resumed', $locked, ['status' => 'SUSPENDED'], ['status' => 'ACTIVE'], [
                'status_before' => 'SUSPENDED',
                'status_after' => 'ACTIVE',
            ], $actor);

            return $locked->refresh();
        });
    }
}
