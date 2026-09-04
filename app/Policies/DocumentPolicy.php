<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('document.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document.manage');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can('document.manage');
    }
}
