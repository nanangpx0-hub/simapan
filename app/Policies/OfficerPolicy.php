<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Officer;
use App\Models\User;

class OfficerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.officer.view');
    }

    public function view(User $user, Officer $officer): bool
    {
        return $user->can('master.officer.view');
    }

    public function create(User $user): bool
    {
        return $user->can('master.officer.manage');
    }

    public function update(User $user, Officer $officer): bool
    {
        return $user->can('master.officer.manage');
    }
}
