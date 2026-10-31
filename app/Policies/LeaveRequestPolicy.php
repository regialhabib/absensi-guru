<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isAdmin() || $leaveRequest->user_id === $user->id;
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->user_id === $user->id;
    }
}
