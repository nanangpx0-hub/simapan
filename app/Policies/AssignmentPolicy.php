<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function view(User $user, Assignment $assignment): bool
    {
        return $user->can('allocation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('allocation.assign');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('allocation.assign');
    }
}
