<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentManifest;
use App\Models\User;

class DocumentManifestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, DocumentManifest $manifest): bool
    {
        return $user->can('document.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document.manage');
    }

    public function update(User $user, DocumentManifest $manifest): bool
    {
        return $user->can('document.manage');
    }
}
