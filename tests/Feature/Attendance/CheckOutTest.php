<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;

class CheckOutTest extends AttendanceTestCase
{
    private function checkInAt(string $time): void
    {
        $this->travelTo(self::WORKDAY.' '.$time);
        $this->checkIn()->assertSessionHasNoErrors();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('attendance.check-out'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_check_out_without_check_in_is_rejected(): void
    {
        $this->checkOut()->assertSessionHasErrors('attendance');

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_check_out_after_the_work_day_records_no_early_leave(): void
    {
        $this->checkInAt('06:50:00');
        $this->travelTo(self::WORKDAY.' 14:05:00');

        $this->checkOut(['face_detected' => 0])->assertRedirect(route('attendance.show'))->assertSessionHasNoErrors();

        $attendance = Attendance::query()->sole();
        $this->assertSame('14:05:00', $attendance->check_out_at->format('H:i:s'));
        $this->assertSame(0, $attendance->early_leave_minutes);
        $this->assertFalse($attendance->check_out_face_detected);
        Storage::disk('local')->assertExists($attendance->check_out_photo);
        $this->assertNotSame($attendance->check_in_photo, $attendance->check_out_photo);
    }

    public function test_early_check_out_records_early_leave_minutes(): void
    {
        $this->checkInAt('06:50:00');
        $this->travelTo(self::WORKDAY.' 12:30:00');

        $this->checkOut()->assertSessionHasNoErrors();

        $this->assertSame(90, Attendance::query()->sole()->early_leave_minutes);
    }

    public function test_double_check_out_is_rejected_and_keeps_the_first(): void
    {
        $this->checkInAt('06:50:00');
        $this->travelTo(self::WORKDAY.' 14:05:00');
        $this->checkOut()->assertSessionHasNoErrors();
        $first = Attendance::query()->sole();

        $this->travelTo(self::WORKDAY.' 14:10:00');
        $this->checkOut()->assertSessionHasErrors('attendance');

        $this->assertSame($first->check_out_photo, Attendance::query()->sole()->check_out_photo);
        $this->assertCount(2, Storage::disk('local')->allFiles());
    }

    public function test_check_out_is_rejected_after_closing_time(): void
    {
        $this->checkInAt('06:50:00');
        $this->travelTo(self::WORKDAY.' 18:01:00');

        $this->checkOut()->assertSessionHasErrors('attendance');
    }

    public function test_check_out_outside_radius_is_rejected(): void
    {
        $this->checkInAt('06:50:00');

        $this->checkOut(['longitude' => self::SCHOOL_LONGITUDE + 0.01])->assertSessionHasErrors('location');

        $this->assertNull(Attendance::query()->sole()->check_out_at);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_check_out_with_poor_accuracy_is_rejected(): void
    {
        $this->checkInAt('06:50:00');

        $this->checkOut(['accuracy' => 51])->assertSessionHasErrors('location');

        $this->assertNull(Attendance::query()->sole()->check_out_at);
    }
}
