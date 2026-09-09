<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Student $student): bool
    {
        return $user->isAdmin() || $user->isScanner();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }

    public function manageQr(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }

    public function viewPhoto(User $user, Student $student): bool
    {
        return $user->isAdmin() || $user->isScanner();
    }
}
