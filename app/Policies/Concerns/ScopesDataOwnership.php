<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\Allocation;
use App\Models\User;

trait ScopesDataOwnership
{
    /**
     * Role Administrator/Pimpinan/Operator memiliki akses wilayah penuh;
     * role teknis PPL/PML/Pengolahan dibatasi pada data yang ditugaskan.
     * Pengguna dengan permission langsung (tanpa role teknis) tidak di-scope.
     */
    protected function hasUnrestrictedScope(User $user): bool
    {
        return ! $user->hasScopedDataAccess();
    }

    /**
     * Apakah alokasi ditugaskan (assignment aktif) kepada petugas user?
     */
    protected function allocationAssignedTo(User $user, Allocation $allocation): bool
    {
        $officerId = $user->officerId();

        if ($officerId === null) {
            return false;
        }

        return $allocation->activeAssignments()
            ->where('officer_id', $officerId)
            ->exists();
    }
}
