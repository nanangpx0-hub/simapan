<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkUnit;

class WorkUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.work_unit.view');
    }

    public function view(User $user, WorkUnit $workUnit): bool
    {
        return $user->can('master.work_unit.view');
    }

    public function create(User $user): bool
    {
        return $user->can('master.work_unit.manage');
    }

    public function update(User $user, WorkUnit $workUnit): bool
    {
        return $user->can('master.work_unit.manage');
    }
}
