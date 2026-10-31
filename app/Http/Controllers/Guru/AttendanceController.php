<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceSubmissionRequest;
use App\Models\SchoolSetting;
use App\Models\WorkSchedule;
use App\Services\Attendance\AttendanceRecorder;
use App\Services\Attendance\DailyStatusResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function show(Request $request, AttendanceRecorder $recorder, DailyStatusResolver $resolver): Response
    {
        $user = $request->user();
        $now = now();
        $schedule = WorkSchedule::forDate($now);
        $isWorkday = (bool) $schedule?->is_workday;
        $setting = SchoolSetting::current();
        $attendance = $user->attendances()->whereDate('date', $now->toDateString())->first();

        return Inertia::render('attendance/check', [
            'today' => [
                'date' => $now->toDateString(),
                'isWorkday' => $isWorkday,
                'startsAt' => $isWorkday ? $this->hourAndMinute($schedule->starts_at) : null,
                'endsAt' => $isWorkday ? $this->hourAndMinute($schedule->ends_at) : null,
                'status' => $resolver->resolve($user, $now)->value,
            ],
            'attendance' => $attendance === null ? null : [
                'checkInAt' => $attendance->check_in_at->toIso8601String(),
                'checkOutAt' => $attendance->check_out_at?->toIso8601String(),
                'status' => $attendance->status->value,
                'lateMinutes' => $attendance->late_minutes,
                'earlyLeaveMinutes' => $attendance->early_leave_minutes,
            ],
            'school' => [
                'name' => $setting->name,
                'latitude' => $setting->latitude,
                'longitude' => $setting->longitude,
                'radiusMeters' => $setting->radius_meters,
                'maxAccuracyMeters' => $setting->max_accuracy_meters,
            ],
            ...$recorder->availability($user),
        ]);
    }

    public function checkIn(AttendanceSubmissionRequest $request, AttendanceRecorder $recorder): RedirectResponse
    {
        $recorder->checkIn(
            $request->user(),
            $request->file('photo'),
            $request->float('latitude'),
            $request->float('longitude'),
            $request->float('accuracy'),
            $request->boolean('face_detected'),
            $request->ip(),
            $request->userAgent(),
        );

        return to_route('attendance.show');
    }

    public function checkOut(AttendanceSubmissionRequest $request, AttendanceRecorder $recorder): RedirectResponse
    {
        $recorder->checkOut(
            $request->user(),
            $request->file('photo'),
            $request->float('latitude'),
            $request->float('longitude'),
            $request->float('accuracy'),
            $request->boolean('face_detected'),
        );

        return to_route('attendance.show');
    }

    private function hourAndMinute(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}
