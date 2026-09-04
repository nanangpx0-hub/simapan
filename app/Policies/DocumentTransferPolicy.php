<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentTransfer;
use App\Models\User;

class DocumentTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, DocumentTransfer $transfer): bool
    {
        return $user->can('document.view');
    }

    public function create(User $user): bool
    {
        return $user->can('document.receive');
    }

    public function update(User $user, DocumentTransfer $transfer): bool
    {
        return $user->can('document.receive');
    }
}
