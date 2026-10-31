<?php

namespace App\Http\Controllers\Guru;

use App\Enums\LeaveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $leaves = $request->user()->leaveRequests()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('leaves/index', [
            'leaves' => $leaves,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('leaves/create', [
            'types' => collect(LeaveType::cases())->map(fn (LeaveType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->all(),
        ]);
    }

    public function store(StoreLeaveRequest $request, LeaveRequestService $service): RedirectResponse
    {
        $service->submit(
            $request->user(),
            [
                'type' => $request->string('type')->toString(),
                'start_date' => $request->string('start_date')->toString(),
                'end_date' => $request->string('end_date')->toString(),
                'reason' => $request->string('reason')->toString(),
            ],
            $request->file('attachment'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan izin berhasil dikirim.']);

        return to_route('leaves.index');
    }

    public function destroy(LeaveRequest $leaveRequest, LeaveRequestService $service): RedirectResponse
    {
        Gate::authorize('delete', $leaveRequest);

        $service->cancel($leaveRequest);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan izin dibatalkan.']);

        return to_route('leaves.index');
    }

    public function attachment(LeaveRequest $leaveRequest): StreamedResponse
    {
        Gate::authorize('view', $leaveRequest);

        abort_if($leaveRequest->attachment === null, 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($leaveRequest->attachment), 404);

        return $disk->response($leaveRequest->attachment, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
