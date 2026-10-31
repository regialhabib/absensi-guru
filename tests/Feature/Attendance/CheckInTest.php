<?php

namespace Tests\Feature\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

class CheckInTest extends AttendanceTestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('attendance.check-in'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_check_in_inside_radius_records_attendance(): void
    {
        $this->checkIn()->assertRedirect(route('attendance.show'))->assertSessionHasNoErrors();

        $attendance = Attendance::query()->where('user_id', $this->teacher->id)->sole();

        $this->assertSame(self::WORKDAY, $attendance->date->toDateString());
        $this->assertSame(AttendanceStatus::Hadir, $attendance->status);
        $this->assertSame(0, $attendance->late_minutes);
        $this->assertTrue($attendance->check_in_face_detected);
        $this->assertSame('06:50:00', $attendance->check_in_at->format('H:i:s'));
        $this->assertSame(0.0, (float) $attendance->check_in_distance);
    }

    public function test_check_in_stores_ip_address_and_trimmed_user_agent(): void
    {
        $this->actingAs($this->teacher)
            ->withHeader('User-Agent', str_repeat('a', 400))
            ->post(route('attendance.check-in'), $this->payload());

        $attendance = Attendance::query()->sole();

        $this->assertNotNull($attendance->ip_address);
        $this->assertSame(255, strlen($attendance->user_agent));
    }

    public function test_face_detection_flag_never_blocks_check_in(): void
    {
        $this->checkIn(['face_detected' => 0])->assertSessionHasNoErrors();

        $this->assertFalse(Attendance::query()->sole()->check_in_face_detected);
    }

    public function test_check_in_outside_radius_is_rejected_with_distance(): void
    {
        $this->checkIn(['latitude' => self::SCHOOL_LATITUDE + 0.01])
            ->assertSessionHasErrors('location');

        $this->assertStringContainsString('meter dari sekolah', session('errors')->first('location'));
        $this->assertSame(0, Attendance::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_check_in_with_poor_accuracy_is_rejected(): void
    {
        $this->checkIn(['accuracy' => 80])->assertSessionHasErrors('location');

        $this->assertStringContainsString('80 meter', session('errors')->first('location'));
        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_check_in_is_rejected_when_school_location_is_not_set(): void
    {
        SchoolSetting::current()->update(['latitude' => null, 'longitude' => null]);

        $this->checkIn()->assertSessionHasErrors('attendance');

        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_check_in_is_rejected_on_a_non_workday(): void
    {
        $this->travelTo(self::SUNDAY.' 08:00:00');

        $this->checkIn()->assertSessionHasErrors('attendance');
    }

    public function test_check_in_is_rejected_during_approved_leave(): void
    {
        LeaveRequest::factory()->for($this->teacher)->create([
            'status' => LeaveStatus::Approved,
            'start_date' => self::WORKDAY,
            'end_date' => self::WORKDAY,
        ]);

        $this->checkIn()->assertSessionHasErrors('attendance');
    }

    public function test_pending_leave_does_not_block_check_in(): void
    {
        LeaveRequest::factory()->for($this->teacher)->create([
            'status' => LeaveStatus::Pending,
            'start_date' => self::WORKDAY,
            'end_date' => self::WORKDAY,
        ]);

        $this->checkIn()->assertSessionHasNoErrors();
    }

    public function test_check_in_is_rejected_before_opening_time(): void
    {
        $this->travelTo(self::WORKDAY.' 05:29:00');

        $this->checkIn()->assertSessionHasErrors('attendance');
    }

    public function test_check_in_is_rejected_after_the_work_day_ends(): void
    {
        $this->travelTo(self::WORKDAY.' 14:01:00');

        $this->checkIn()->assertSessionHasErrors('attendance');
    }

    #[DataProvider('arrivalTimes')]
    public function test_status_depends_on_arrival_time(string $time, AttendanceStatus $status, int $lateMinutes): void
    {
        $this->travelTo(self::WORKDAY.' '.$time);

        $this->checkIn()->assertSessionHasNoErrors();

        $attendance = Attendance::query()->sole();
        $this->assertSame($status, $attendance->status);
        $this->assertSame($lateMinutes, $attendance->late_minutes);
    }

    /**
     * @return array<string, array{string, AttendanceStatus, int}>
     */
    public static function arrivalTimes(): array
    {
        return [
            'before start' => ['06:00:00', AttendanceStatus::Hadir, 0],
            'exactly at start' => ['07:00:00', AttendanceStatus::Hadir, 0],
            'within tolerance' => ['07:10:00', AttendanceStatus::Hadir, 0],
            'just past tolerance' => ['07:11:00', AttendanceStatus::Terlambat, 11],
            'late' => ['07:45:30', AttendanceStatus::Terlambat, 45],
        ];
    }

    public function test_double_check_in_is_rejected_without_replacing_the_first(): void
    {
        $this->checkIn()->assertSessionHasNoErrors();
        $first = Attendance::query()->sole();

        $this->checkIn()->assertSessionHasErrors('attendance');

        $this->assertSame(1, Attendance::query()->count());
        $this->assertSame($first->check_in_photo, Attendance::query()->sole()->check_in_photo);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_photo_is_stored_privately_under_the_dated_path(): void
    {
        $this->checkIn();

        $path = Attendance::query()->sole()->check_in_photo;

        $this->assertMatchesRegularExpression('#^attendances/2026/11/[0-9a-f-]{36}\.jpg$#', $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function test_invalid_input_is_rejected(array $overrides, string $field): void
    {
        $this->checkIn($overrides)->assertSessionHasErrors($field);

        $this->assertSame(0, Attendance::query()->count());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'photo missing' => [['photo' => null], 'photo'],
            'photo not an image' => [['photo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], 'photo'],
            'photo gif' => [['photo' => UploadedFile::fake()->image('a.gif')], 'photo'],
            'photo too large' => [['photo' => UploadedFile::fake()->image('a.jpg')->size(3000)], 'photo'],
            'latitude missing' => [['latitude' => null], 'latitude'],
            'latitude out of range' => [['latitude' => 120], 'latitude'],
            'accuracy missing' => [['accuracy' => null], 'accuracy'],
            'face flag missing' => [['face_detected' => null], 'face_detected'],
        ];
    }

    public function test_webp_photo_is_accepted(): void
    {
        $this->checkIn(['photo' => UploadedFile::fake()->image('a.webp')])->assertSessionHasNoErrors();

        $this->assertSame(1, Attendance::query()->count());
    }

    public function test_inactive_teacher_cannot_check_in(): void
    {
        $this->checkIn(user: User::factory()->guru()->inactive()->create())->assertRedirect();

        $this->assertSame(0, Attendance::query()->count());
    }
}
