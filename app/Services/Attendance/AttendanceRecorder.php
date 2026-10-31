<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceRecorder
{
    private const string PHOTO_DISK = 'local';

    public function __construct(private GeoFence $geoFence) {}

    /**
     * Whether the teacher can check in or out right now, and why not when neither is possible.
     *
     * @return array{canCheckIn: bool, canCheckOut: bool, blockedReason: string|null}
     */
    public function availability(User $user): array
    {
        $now = now();
        $attendance = $this->todayAttendance($user, $now);

        $checkInBlocker = $attendance === null
            ? $this->checkInBlocker($user, $now)
            : 'Anda sudah absen masuk hari ini.';
        $checkOutBlocker = $this->checkOutBlocker($attendance, $now);

        $canCheckIn = $checkInBlocker === null;
        $canCheckOut = $checkOutBlocker === null;

        return [
            'canCheckIn' => $canCheckIn,
            'canCheckOut' => $canCheckOut,
            'blockedReason' => $canCheckIn || $canCheckOut
                ? null
                : ($attendance === null ? $checkInBlocker : $checkOutBlocker),
        ];
    }

    /**
     * Record today's check-in for the teacher.
     *
     * @throws ValidationException when any attendance rule rejects the request
     */
    public function checkIn(
        User $user,
        UploadedFile $photo,
        float $latitude,
        float $longitude,
        float $accuracy,
        bool $faceDetected,
        ?string $ipAddress,
        ?string $userAgent,
    ): Attendance {
        $now = now();

        if ($this->todayAttendance($user, $now) !== null) {
            $this->fail('attendance', 'Anda sudah absen masuk hari ini.');
        }

        $blocker = $this->checkInBlocker($user, $now);

        if ($blocker !== null) {
            $this->fail('attendance', $blocker);
        }

        $distance = $this->assertWithinLocationLimits($latitude, $longitude, $accuracy);

        $startsAt = $this->timeOn($now, (string) WorkSchedule::forDate($now)?->starts_at);
        $lateToleranceMinutes = SchoolSetting::current()->late_tolerance_minutes;
        $isLate = $now->greaterThan($startsAt->copy()->addMinutes($lateToleranceMinutes));

        $path = $this->storePhoto($photo, $now);

        try {
            return $user->attendances()->create([
                'date' => $now->toDateString(),
                'check_in_at' => $now,
                'check_in_latitude' => $latitude,
                'check_in_longitude' => $longitude,
                'check_in_accuracy' => round($accuracy, 2),
                'check_in_distance' => round($distance, 2),
                'check_in_photo' => $path,
                'check_in_face_detected' => $faceDetected,
                'status' => $isLate ? AttendanceStatus::Terlambat : AttendanceStatus::Hadir,
                'late_minutes' => $isLate ? $this->minutesBetween($startsAt, $now) : 0,
                'ip_address' => $ipAddress === null ? null : Str::limit($ipAddress, 255, ''),
                'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 255, ''),
            ]);
        } catch (UniqueConstraintViolationException) {
            Storage::disk(self::PHOTO_DISK)->delete($path);

            $this->fail('attendance', 'Anda sudah absen masuk hari ini.');
        }
    }

    /**
     * Record today's check-out for the teacher.
     *
     * @throws ValidationException when any attendance rule rejects the request
     */
    public function checkOut(
        User $user,
        UploadedFile $photo,
        float $latitude,
        float $longitude,
        float $accuracy,
        bool $faceDetected,
    ): Attendance {
        $now = now();
        $attendance = $this->todayAttendance($user, $now);

        if ($attendance === null) {
            $this->fail('attendance', 'Anda belum absen masuk hari ini.');
        }

        $blocker = $this->checkOutBlocker($attendance, $now);

        if ($blocker !== null) {
            $this->fail('attendance', $blocker);
        }

        $distance = $this->assertWithinLocationLimits($latitude, $longitude, $accuracy);

        $endsAt = $this->timeOn($now, WorkSchedule::forDate($now)->ends_at ?? '00:00');
        $earlyLeaveMinutes = $now->lessThan($endsAt) ? $this->minutesBetween($now, $endsAt) : 0;

        $path = $this->storePhoto($photo, $now);

        $updated = Attendance::query()
            ->whereKey($attendance->id)
            ->whereNull('check_out_at')
            ->update([
                'check_out_at' => $now,
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_accuracy' => round($accuracy, 2),
                'check_out_distance' => round($distance, 2),
                'check_out_photo' => $path,
                'check_out_face_detected' => $faceDetected,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'updated_at' => $now,
            ]);

        if ($updated === 0) {
            Storage::disk(self::PHOTO_DISK)->delete($path);

            $this->fail('attendance', 'Anda sudah absen pulang hari ini.');
        }

        return $attendance->refresh();
    }

    private function todayAttendance(User $user, CarbonInterface $now): ?Attendance
    {
        return $user->attendances()->whereDate('date', $now->toDateString())->first();
    }

    /**
     * Why the teacher cannot check in at this moment, or null when allowed (distance excluded).
     */
    private function checkInBlocker(User $user, CarbonInterface $now): ?string
    {
        $setting = SchoolSetting::current();

        if ($setting->latitude === null || $setting->longitude === null) {
            return 'Lokasi sekolah belum diatur. Hubungi admin.';
        }

        $schedule = WorkSchedule::forDate($now);

        if (! $schedule?->is_workday || $schedule->starts_at === null || $schedule->ends_at === null) {
            return 'Hari ini bukan hari kerja.';
        }

        $onLeave = $user->leaveRequests()
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $now->toDateString())
            ->whereDate('end_date', '>=', $now->toDateString())
            ->exists();

        if ($onLeave) {
            return 'Anda sedang dalam izin yang disetujui hari ini.';
        }

        $opensAt = $this->timeOn($now, $setting->check_in_opens_at);

        if ($now->lessThan($opensAt)) {
            return 'Absen masuk baru dibuka pukul '.$opensAt->format('H:i').'.';
        }

        if ($now->greaterThan($this->timeOn($now, $schedule->ends_at))) {
            return 'Waktu absen masuk hari ini sudah ditutup.';
        }

        return null;
    }

    /**
     * Why the teacher cannot check out at this moment, or null when allowed (distance excluded).
     */
    private function checkOutBlocker(?Attendance $attendance, CarbonInterface $now): ?string
    {
        if ($attendance === null) {
            return 'Anda belum absen masuk hari ini.';
        }

        if ($attendance->check_out_at !== null) {
            return 'Anda sudah absen pulang hari ini.';
        }

        $closesAt = $this->timeOn($now, SchoolSetting::current()->check_out_closes_at);

        if ($now->greaterThan($closesAt)) {
            return 'Waktu absen pulang hari ini sudah ditutup.';
        }

        return null;
    }

    /**
     * Returns the distance from the school in meters.
     *
     * @throws ValidationException when the teacher is too far away or the GPS is too imprecise
     */
    private function assertWithinLocationLimits(float $latitude, float $longitude, float $accuracy): float
    {
        $setting = SchoolSetting::current();

        if ($setting->latitude === null || $setting->longitude === null) {
            $this->fail('attendance', 'Lokasi sekolah belum diatur. Hubungi admin.');
        }

        if ($accuracy > $setting->max_accuracy_meters) {
            $this->fail('location', sprintf(
                'Akurasi lokasi Anda %d meter, melebihi batas %d meter. Coba lagi di tempat terbuka.',
                round($accuracy),
                $setting->max_accuracy_meters,
            ));
        }

        $distance = $this->geoFence->distanceInMeters($latitude, $longitude, $setting->latitude, $setting->longitude);

        if ($distance > $setting->radius_meters) {
            $this->fail('location', sprintf(
                'Anda berada %d meter dari sekolah, di luar radius %d meter.',
                round($distance),
                $setting->radius_meters,
            ));
        }

        return $distance;
    }

    private function storePhoto(UploadedFile $photo, CarbonInterface $now): string
    {
        $path = Storage::disk(self::PHOTO_DISK)->putFileAs(
            'attendances/'.$now->format('Y/m'),
            $photo,
            Str::uuid()->toString().'.jpg',
        );

        return $path === false ? $this->fail('attendance', 'Foto gagal disimpan. Coba lagi.') : $path;
    }

    private function timeOn(CarbonInterface $day, string $time): CarbonInterface
    {
        return $day->copy()->setTimeFromTimeString($time);
    }

    private function minutesBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) floor($from->diffInMinutes($to, true));
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
