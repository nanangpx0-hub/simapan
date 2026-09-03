<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.region.view');
    }

    public function view(User $user, Region $region): bool
    {
        return $user->can('master.region.view');
    }

    public function create(User $user): bool
    {
        return $user->can('master.region.manage');
    }

    public function update(User $user, Region $region): bool
    {
        return $user->can('master.region.manage');
    }
}
