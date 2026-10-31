<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Only the teacher who owns the record, or an admin, may view it and its photos.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->isAdmin() || $user->id === $attendance->user_id;
    }
}
