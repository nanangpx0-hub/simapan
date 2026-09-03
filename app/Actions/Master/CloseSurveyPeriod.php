<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\SurveyPeriod;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseSurveyPeriod
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

            if (! $locked->canTransitionTo('CLOSED')) {
                throw ValidationException::withMessages([
                    'status' => 'Periode status '.$locked->status.' tidak dapat ditutup.',
                ]);
            }

            $locked->status = 'CLOSED';
            $locked->closed_by = $actor->getKey();
            $locked->closed_at = now();
            $locked->save();

            AuditLogger::log('closed', $locked, ['status' => 'ACTIVE'], ['status' => 'CLOSED'], [
                'status_before' => 'ACTIVE',
                'status_after' => 'CLOSED',
            ], $actor);

            return $locked->refresh();
        });
    }
}
