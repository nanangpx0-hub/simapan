<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnassignOfficer
{
    /**
     * @throws ValidationException
     */
    public function handle(Assignment $assignment, User $actor): Assignment
    {
        return DB::transaction(function () use ($assignment): Assignment {
            $locked = Assignment::query()
                ->whereKey($assignment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $allocation = $locked->allocation()->firstOrFail();

            if (! $allocation->isAssignmentEditable()) {
                throw ValidationException::withMessages([
                    'allocation' => 'Penugasan tidak dapat dikelola pada status '.$allocation->status.'.',
                ]);
            }

            if (! $locked->is_active) {
                throw ValidationException::withMessages([
                    'assignment' => 'Penugasan sudah tidak aktif.',
                ]);
            }

            $locked->is_active = false;
            $locked->ended_at = now();
            $locked->save();

            return $locked->refresh();
        });
    }
}
