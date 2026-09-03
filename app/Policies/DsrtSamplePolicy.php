<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DsrtSample;
use App\Models\User;

class DsrtSamplePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dsrt.view');
    }

    public function view(User $user, DsrtSample $sample): bool
    {
        return $user->can('dsrt.view');
    }

    public function create(User $user): bool
    {
        return $user->can('dsrt.manage');
    }

    public function update(User $user, DsrtSample $sample): bool
    {
        return $user->can('dsrt.manage');
    }
}
