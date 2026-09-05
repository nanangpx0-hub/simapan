<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('admin.role.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('admin.role.manage');
    }

    /**
     * Membuat role baru — hanya Super Admin.
     */
    public function create(User $user): bool
    {
        return $user->can('super-admin');
    }

    /**
     * Mengubah role (sinkronisasi permission/rename) — hanya Super Admin.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('super-admin');
    }

    /**
     * Menghapus role — hanya Super Admin.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('super-admin');
    }
}
