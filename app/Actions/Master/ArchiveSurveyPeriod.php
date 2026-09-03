<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\SurveyPeriod;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveSurveyPeriod
{
    /**
     * @throws ValidationException
     */
    public function handle(SurveyPeriod $period, User $actor): SurveyPeriod
    {
        return DB::transaction(function () use ($period, $actor): SurveyPeriod {
            $locked = SurveyPeriod::query()
                ->whereKey($period->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->canTransitionTo('ARCHIVED')) {
                throw ValidationException::withMessages([
                    'status' => 'Periode status '.$locked->status.' tidak dapat diarsipkan.',
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
