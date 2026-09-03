<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OfficerAlias;
use App\Models\User;

class OfficerAliasPolicy
{
    public function view(User $user, OfficerAlias $alias): bool
    {
        return $user->can('master.officer.view');
    }

    public function create(User $user): bool
    {
        return $user->can('master.officer.manage');
    }

    public function update(User $user, OfficerAlias $alias): bool
    {
        return $user->can('master.officer.manage');
    }
}
