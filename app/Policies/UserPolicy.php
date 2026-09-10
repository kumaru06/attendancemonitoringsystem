<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $model->isManageableAccount();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $model->isManageableAccount();
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->isSuperAdmin() || $user->is($model) || ! $model->isManageableAccount()) {
            return false;
        }

        if ($model->isAdmin() && $model->schoolHasStudents()) {
            return false;
        }

        return true;
    }
}
