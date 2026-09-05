<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ScopesDataOwnership;

class DocumentPolicy
{
    use ScopesDataOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('document.view');
    }

    public function view(User $user, Document $document): bool
    {
        if (! $user->can('document.view')) {
            return false;
        }

        return $this->hasUnrestrictedScope($user)
            || $this->documentAssignedTo($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->can('document.manage');
    }

    public function update(User $user, Document $document): bool
    {
        if (! $user->can('document.manage')) {
            return false;
        }

        return $this->hasUnrestrictedScope($user)
            || $this->documentAssignedTo($user, $document);
    }

    /**
     * Dokumen terkait petugas bila alokasinya ditugaskan (PPL/PML)
     * atau dokumen di-assign untuk pengolahan (Pengolahan).
     */
    private function documentAssignedTo(User $user, Document $document): bool
    {
        $officerId = $user->officerId();

        if ($officerId === null) {
            return false;
        }

        $allocation = $document->allocation;

        if ($allocation !== null && $this->allocationAssignedTo($user, $allocation)) {
            return true;
        }

        return $document->processingAssignments()
            ->where('officer_id', $officerId)
            ->where('status', 'ACTIVE')
            ->exists();
    }
}
