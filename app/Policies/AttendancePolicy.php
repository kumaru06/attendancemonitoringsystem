<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isScanner() && (int) $attendance->recorded_by === (int) $user->id;
    }

    public function export(User $user): bool
    {
        return $user->isAdmin();
    }

    public function scan(User $user): bool
    {
        return $user->isAdmin() || $user->isScanner();
    }
}
