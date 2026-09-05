<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DsrtSample;
use App\Models\User;
use App\Policies\Concerns\ScopesDataOwnership;

class DsrtSamplePolicy
{
    use ScopesDataOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('dsrt.view');
    }

    public function view(User $user, DsrtSample $sample): bool
    {
        if (! $user->can('dsrt.view')) {
            return false;
        }

        return $this->hasUnrestrictedScope($user)
            || $this->allocationAssignedTo($user, $sample->allocation);
    }

    public function create(User $user): bool
    {
        return $user->can('dsrt.manage');
    }

    public function update(User $user, DsrtSample $sample): bool
    {
        if (! $user->can('dsrt.manage') && ! $user->can('dsrt.verify')) {
            return false;
        }

        return $this->hasUnrestrictedScope($user)
            || $this->allocationAssignedTo($user, $sample->allocation);
    }
}
