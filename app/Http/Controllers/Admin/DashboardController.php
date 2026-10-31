<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\AttendanceReportService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(AttendanceReportService $reports): Response
    {
        return Inertia::render('admin/dashboard', [
            'today' => today()->toDateString(),
            'summary' => $reports->todaySummary(),
            'pendingLeaves' => LeaveRequest::query()->where('status', LeaveStatus::Pending)->count(),
        ]);
    }
}
