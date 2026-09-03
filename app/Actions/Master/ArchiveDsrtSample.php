<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\DsrtSample;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveDsrtSample
{
    /**
     * @throws ValidationException
     */
    public function handle(DsrtSample $sample, User $actor): DsrtSample
    {
        return DB::transaction(function () use ($sample, $actor): DsrtSample {
            $locked = DsrtSample::query()
                ->whereKey($sample->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->canTransitionTo('ARCHIVED')) {
                throw ValidationException::withMessages([
                    'record_status' => 'Sampel status '.$locked->record_status.' tidak dapat diarsipkan.',
                ]);
            }

            $from = $locked->record_status;

            $locked->record_status = 'ARCHIVED';
            $locked->archived_by = $actor->getKey();
            $locked->archived_at = now();
            $locked->save();

            AuditLogger::log('archived', $locked, ['record_status' => $from], ['record_status' => 'ARCHIVED'], [
                'status_before' => $from,
                'status_after' => 'ARCHIVED',
            ], $actor);

            return $locked->refresh();
        });
    }
}
