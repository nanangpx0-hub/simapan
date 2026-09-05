<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Allocation;
use App\Models\User;
use App\Policies\Concerns\ScopesDataOwnership;

class AllocationPolicy
{
    use ScopesDataOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('allocation.view');
    }

    public function view(User $user, Allocation $allocation): bool
    {
        if (! $user->can('allocation.view')) {
            return false;
        }

        return $this->hasUnrestrictedScope($user)
            || $this->allocationAssignedTo($user, $allocation);
    }

    public function create(User $user): bool
    {
        return $user->can('allocation.manage');
    }

    public function update(User $user, Allocation $allocation): bool
    {
        if (! $user->can('allocation.manage')) {
            return false;
        }

        return $this->hasUnrestrictedScope($user)
            || $this->allocationAssignedTo($user, $allocation);
    }
}
