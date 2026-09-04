<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentProcessingAssignment;
use App\Models\User;

class DocumentProcessingAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, DocumentProcessingAssignment $assignment): bool
    {
        return $user->can('document.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document.assign');
    }

    public function update(User $user, DocumentProcessingAssignment $assignment): bool
    {
        return $user->can('document.assign');
    }
}
