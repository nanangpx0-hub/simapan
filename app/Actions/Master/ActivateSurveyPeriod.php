<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\SurveyPeriod;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateSurveyPeriod
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

            if (! $locked->canTransitionTo('ACTIVE')) {
                throw ValidationException::withMessages([
                    'status' => 'Periode status '.$locked->status.' tidak dapat diaktifkan.',
                ]);
            }

            $conflict = SurveyPeriod::query()
                ->where('id', '!=', $locked->getKey())
                ->forSlot(
                    $locked->survey_type_id,
                    $locked->year,
                    $locked->period_type,
                    $locked->period_number
                )
                ->active()
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'status' => 'Sudah ada periode aktif pada kombinasi jenis, tahun, tipe, dan nomor ini.',
                ]);
            }

            $locked->status = 'ACTIVE';
            $locked->closed_by = null;
            $locked->closed_at = null;
            $locked->save();

            AuditLogger::log('activated', $locked, ['status' => 'DRAFT'], ['status' => 'ACTIVE'], [
                'status_before' => 'DRAFT',
                'status_after' => 'ACTIVE',
            ], $actor);

            return $locked->refresh();
        });
    }
}
