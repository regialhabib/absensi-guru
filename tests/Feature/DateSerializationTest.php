<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateSerializationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
    }

    public function test_leave_dates_keep_their_calendar_day_in_page_props(): void
    {
        $leave = LeaveRequest::factory()->forDates('2026-11-02', '2026-11-03')->create()->refresh();

        $this->assertSame('2026-11-02', $leave->toArray()['start_date']);
        $this->assertSame('2026-11-03', $leave->toArray()['end_date']);
    }

    public function test_attendance_date_keeps_its_calendar_day_in_page_props(): void
    {
        $attendance = Attendance::factory()->onDate('2026-11-02')->create()->refresh();

        $this->assertSame('2026-11-02', $attendance->toArray()['date']);
    }
}
