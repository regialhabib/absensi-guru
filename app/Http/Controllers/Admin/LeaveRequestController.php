<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveRequestService $leaves) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(LeaveStatus::class)],
        ]);

        $status = $filters['status'] ?? null;

        $leaveRequests = LeaveRequest::query()
            ->with(['user:id,name,nip', 'reviewer:id,name'])
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->orderByRaw('case when status = ? then 0 else 1 end', [LeaveStatus::Pending->value])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/leaves/index', [
            'leaveRequests' => $leaveRequests,
            'filters' => ['status' => $status],
        ]);
    }

    public function approve(ReviewLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->leaves->approve($leaveRequest, $request->user(), $request->validated('note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan izin disetujui.']);

        return back();
    }

    public function reject(ReviewLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->leaves->reject($leaveRequest, $request->user(), $request->validated('note'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan izin ditolak.']);

        return back();
    }
}
