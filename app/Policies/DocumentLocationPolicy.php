<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentLocation;
use App\Models\User;

class DocumentLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, DocumentLocation $documentLocation): bool
    {
        return $user->can('document.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document.manage');
    }

    public function update(User $user, DocumentLocation $documentLocation): bool
    {
        return $user->can('document.manage');
    }
}
