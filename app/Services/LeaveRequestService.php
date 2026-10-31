<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    private const string ATTACHMENT_DISK = 'local';

    /**
     * Submit a pending leave request for the teacher.
     *
     * @param  array{type: LeaveType|string, start_date: string, end_date: string, reason: string}  $data
     *
     * @throws ValidationException when the dates overlap a pending or approved request
     */
    public function submit(User $user, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        return DB::transaction(function () use ($user, $data, $attachment): LeaveRequest {
            // Serializes concurrent submissions by one teacher so the overlap check holds.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $overlaps = $user->leaveRequests()
                ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
                ->whereDate('start_date', '<=', $data['end_date'])
                ->whereDate('end_date', '>=', $data['start_date'])
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'start_date' => 'Tanggal ini bertabrakan dengan pengajuan izin Anda yang lain.',
                ]);
            }

            return $user->leaveRequests()->create([
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'],
                'attachment' => $attachment?->store('leave-attachments', self::ATTACHMENT_DISK),
                'status' => LeaveStatus::Pending,
            ]);
        });
    }

    /**
     * Cancel a pending request and remove its attachment.
     *
     * @throws ValidationException when the request is no longer pending
     */
    public function cancel(LeaveRequest $leave): void
    {
        $deleted = LeaveRequest::query()
            ->whereKey($leave->id)
            ->where('status', LeaveStatus::Pending)
            ->delete();

        if ($deleted === 0) {
            throw $this->alreadyProcessed();
        }

        if ($leave->attachment !== null) {
            Storage::disk(self::ATTACHMENT_DISK)->delete($leave->attachment);
        }
    }

    /**
     * @throws ValidationException when the request is no longer pending
     */
    public function approve(LeaveRequest $leave, User $reviewer, ?string $note = null): void
    {
        $this->review($leave, LeaveStatus::Approved, $reviewer, $note);
    }

    /**
     * @throws ValidationException when the request is no longer pending
     */
    public function reject(LeaveRequest $leave, User $reviewer, ?string $note = null): void
    {
        $this->review($leave, LeaveStatus::Rejected, $reviewer, $note);
    }

    private function review(LeaveRequest $leave, LeaveStatus $status, User $reviewer, ?string $note): void
    {
        $updated = LeaveRequest::query()
            ->whereKey($leave->id)
            ->where('status', LeaveStatus::Pending)
            ->update([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

        if ($updated === 0) {
            throw $this->alreadyProcessed();
        }

        $leave->refresh();
    }

    private function alreadyProcessed(): ValidationException
    {
        return ValidationException::withMessages([
            'status' => 'Pengajuan ini sudah diproses.',
        ]);
    }
}
