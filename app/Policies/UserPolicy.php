<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('admin.user.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('admin.user.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('admin.user.manage');
    }

    public function update(User $user, User $model): bool
    {
        if ($model->hasRole((string) config('simapan_roles.super_role'))) {
            // Akun Super Admin hanya boleh dimodifikasi oleh Super Admin.
            return $user->can('super-admin');
        }

        return $user->can('admin.user.manage');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole((string) config('simapan_roles.super_role'))) {
            // Akun Super Admin hanya boleh dihapus oleh Super Admin.
            return $user->can('super-admin');
        }

        return $user->can('admin.user.manage');
    }
}
