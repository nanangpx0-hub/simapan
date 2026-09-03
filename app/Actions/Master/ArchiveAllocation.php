<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Allocation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveAllocation
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

            if (! $locked->canTransitionTo('ARCHIVED')) {
                throw ValidationException::withMessages([
                    'status' => 'Alokasi status '.$locked->status.' tidak dapat diarsipkan.',
                ]);
            }

            $from = $locked->status;

            $locked->status = 'ARCHIVED';
            $locked->save();

            AuditLogger::log('archived', $locked, ['status' => $from], ['status' => 'ARCHIVED'], [
                'status_before' => $from,
                'status_after' => 'ARCHIVED',
            ], $actor);

            return $locked->refresh();
        });
    }
}
