<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Allocation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuspendAllocation
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

            if (! $locked->canTransitionTo('SUSPENDED')) {
                throw ValidationException::withMessages([
                    'status' => 'Alokasi status '.$locked->status.' tidak dapat ditangguhkan.',
                ]);
            }

            $locked->status = 'SUSPENDED';
            $locked->save();

            AuditLogger::log('suspended', $locked, ['status' => 'ACTIVE'], ['status' => 'SUSPENDED'], [
                'status_before' => 'ACTIVE',
                'status_after' => 'SUSPENDED',
            ], $actor);

            return $locked->refresh();
        });
    }
}
