<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\Officer;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignOfficer
{
    /**
     * @throws ValidationException
     */
    public function handle(
        Allocation $allocation,
        Officer $officer,
        string $role,
        ?string $employmentCategory,
        User $actor
    ): Assignment {
        return DB::transaction(function () use ($allocation, $officer, $role, $employmentCategory, $actor): Assignment {
            $officer = Officer::query()->findOrFail($officer->getKey());

            $locked = Allocation::query()
                ->whereKey($allocation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isAssignmentEditable()) {
                throw ValidationException::withMessages([
                    'allocation' => 'Penugasan tidak dapat dikelola pada status '.$locked->status.'.',
                ]);
            }

            if ($officer->status !== 'ACTIVE' || $officer->trashed()) {
                throw ValidationException::withMessages([
                    'officer_id' => 'Petugas harus aktif dan tidak terhapus.',
                ]);
            }

            $expectedSpatieRole = User::spatieRoleFor($role);

            if ($expectedSpatieRole !== null && $officer->user_id !== null) {
                $linkedUser = User::query()->find($officer->user_id);

                if ($linkedUser instanceof User && ! $linkedUser->hasRole($expectedSpatieRole)) {
                    throw ValidationException::withMessages([
                        'officer_id' => 'Akun tertaut petugas harus memiliki role '.$expectedSpatieRole.' untuk assignment '.$role.'.',
                    ]);
                }
            }

            if (! in_array($role, Assignment::ROLES, true)) {
                throw ValidationException::withMessages([
                    'assignment_role' => 'Role penugasan tidak dikenal.',
                ]);
            }

            // Validasi kesesuaian role: akun user tertaut harus memiliki
            // Spatie role yang setara dengan assignment_role penugasan.
            $expectedSpatieRole = User::spatieRoleFor($role);

            if ($expectedSpatieRole !== null && $officer->user_id !== null) {
                $linkedUser = $officer->user()->first();

                if ($linkedUser !== null && ! $linkedUser->hasRole($expectedSpatieRole)) {
                    throw ValidationException::withMessages([
                        'officer_id' => 'Akun petugas tidak memiliki role '.$expectedSpatieRole.' yang setara dengan penugasan '.$role.'.',
                    ]);
                }
            }

            $previous = Assignment::query()
                ->where('allocation_id', $locked->getKey())
                ->forRole($role)
                ->active()
                ->lockForUpdate()
                ->first();

            $now = now();

            if ($previous instanceof Assignment && (int) $previous->officer_id === (int) $officer->getKey()) {
                throw ValidationException::withMessages([
                    'officer_id' => 'Petugas tersebut sudah aktif pada role ini.',
                ]);
            }

            if ($previous instanceof Assignment) {
                $created = AuditLogger::withoutAudit(function () use ($locked, $officer, $role, $employmentCategory, $actor, $previous, $now): Assignment {
                    $previous->is_active = false;
                    $previous->ended_at = $now;
                    $previous->save();

                    $assignment = new Assignment([
                        'allocation_id' => $locked->getKey(),
                        'officer_id' => $officer->getKey(),
                        'assignment_role' => $role,
                        'employment_category' => $employmentCategory,
                        'is_active' => true,
                        'started_at' => $now,
                        'assigned_by' => $actor->getKey(),
                    ]);
                    $assignment->save();

                    return $assignment;
                });

                AuditLogger::log('reassigned', $created, [], [
                    'officer_id' => $officer->getKey(),
                    'assignment_role' => $role,
                ], [
                    'assignment_role' => $role,
                    'employment_category' => $employmentCategory,
                    'previous_officer_id' => $previous->officer_id,
                    'new_officer_id' => $officer->getKey(),
                ], $actor);

                return $created->refresh();
            }

            $created = new Assignment([
                'allocation_id' => $locked->getKey(),
                'officer_id' => $officer->getKey(),
                'assignment_role' => $role,
                'employment_category' => $employmentCategory,
                'is_active' => true,
                'started_at' => $now,
                'assigned_by' => $actor->getKey(),
            ]);
            $created->save();

            return $created->refresh();
        });
    }
}
