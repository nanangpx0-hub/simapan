<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Allocation;
use App\Models\User;

class AllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('allocation.view');
    }

    public function view(User $user, Allocation $allocation): bool
    {
        return $user->can('allocation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('allocation.manage');
    }

    public function update(User $user, Allocation $allocation): bool
    {
        return $user->can('allocation.manage');
    }
}
