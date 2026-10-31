<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;

class LeaveRequestController extends Controller
{
    public function index(): never
    {
        abort(501);
    }

    public function approve(LeaveRequest $leaveRequest): never
    {
        abort(501);
    }

    public function reject(LeaveRequest $leaveRequest): never
    {
        abort(501);
    }
}
