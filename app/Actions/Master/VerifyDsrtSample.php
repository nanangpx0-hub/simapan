<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\DsrtSample;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyDsrtSample
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

            if (! $locked->canTransitionTo('VERIFIED')) {
                throw ValidationException::withMessages([
                    'record_status' => 'Sampel status '.$locked->record_status.' tidak dapat diverifikasi.',
                ]);
            }

            $this->assertVerifiable($locked);

            $locked->record_status = 'VERIFIED';
            $locked->verified_by = $actor->getKey();
            $locked->verified_at = now();
            $locked->save();

            AuditLogger::log('verified', $locked, ['record_status' => 'DRAFT'], ['record_status' => 'VERIFIED'], [
                'status_before' => 'DRAFT',
                'status_after' => 'VERIFIED',
            ], $actor);

            return $locked->refresh();
        });
    }

    /**
     * @throws ValidationException
     */
    private function assertVerifiable(DsrtSample $sample): void
    {
        if (trim($sample->nus) === '' || trim($sample->nurt) === '') {
            throw ValidationException::withMessages([
                'record_status' => 'NUS dan NURT wajib terisi untuk verifikasi.',
            ]);
        }

        if (trim($sample->krt_name) === '') {
            throw ValidationException::withMessages([
                'record_status' => 'Nama KRT wajib terisi untuk verifikasi.',
            ]);
        }

        if (! in_array($sample->enumeration_status, DsrtSample::ENUMERATION_STATUSES, true)) {
            throw ValidationException::withMessages([
                'record_status' => 'Status pencacahan tidak valid untuk verifikasi.',
            ]);
        }

        if (
            in_array($sample->enumeration_status, DsrtSample::NOTE_REQUIRED_STATUSES, true)
            && trim((string) ($sample->notes ?? '')) === ''
        ) {
            throw ValidationException::withMessages([
                'record_status' => 'Catatan wajib untuk status pencacahan ini.',
            ]);
        }
    }
}
