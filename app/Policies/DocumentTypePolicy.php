<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;

class DocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, DocumentType $documentType): bool
    {
        return $user->can('document.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document.manage');
    }

    public function update(User $user, DocumentType $documentType): bool
    {
        return $user->can('document.manage');
    }
}
