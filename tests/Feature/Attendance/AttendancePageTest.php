<?php

namespace Tests\Feature\Attendance;

use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SchoolSetting;
use Inertia\Testing\AssertableInertia as Assert;

class AttendancePageTest extends AttendanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('attendance.show'))->assertRedirect(route('login'));
    }

    public function test_page_exposes_contract_props_before_check_in(): void
    {
        $this->actingAs($this->teacher)->get(route('attendance.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('attendance/check')
                ->where('today.date', self::WORKDAY)
                ->where('today.isWorkday', true)
                ->where('today.startsAt', '07:00')
                ->where('today.endsAt', '14:00')
                ->where('today.status', 'belum_absen')
                ->where('attendance', null)
                ->where('school.name', SchoolSetting::current()->name)
                ->where('school.latitude', self::SCHOOL_LATITUDE)
                ->where('school.longitude', self::SCHOOL_LONGITUDE)
                ->where('school.radiusMeters', 100)
                ->where('school.maxAccuracyMeters', 50)
                ->where('canCheckIn', true)
                ->where('canCheckOut', false)
                ->where('blockedReason', null));
    }

    public function test_page_after_check_in_allows_only_check_out(): void
    {
        Attendance::factory()->for($this->teacher)->late(20)->create();

        $this->actingAs($this->teacher)->get(route('attendance.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('today.status', 'terlambat')
                ->where('attendance.status', 'terlambat')
                ->where('attendance.lateMinutes', 20)
                ->where('attendance.checkOutAt', null)
                ->where('canCheckIn', false)
                ->where('canCheckOut', true)
                ->where('blockedReason', null));
    }

    public function test_page_after_check_out_is_blocked_with_reason(): void
    {
        Attendance::factory()->for($this->teacher)->checkedOut()->create();

        $this->actingAs($this->teacher)->get(route('attendance.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canCheckIn', false)
                ->where('canCheckOut', false)
                ->where('blockedReason', 'Anda sudah absen pulang hari ini.'));
    }

    public function test_page_on_non_workday_is_blocked(): void
    {
        $this->travelTo(self::SUNDAY.' 08:00:00');

        $this->actingAs($this->teacher)->get(route('attendance.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('today.isWorkday', false)
                ->where('today.startsAt', null)
                ->where('today.endsAt', null)
                ->where('today.status', 'libur')
                ->where('canCheckIn', false)
                ->where('blockedReason', 'Hari ini bukan hari kerja.'));
    }

    public function test_page_during_approved_leave_is_blocked(): void
    {
        LeaveRequest::factory()->for($this->teacher)->create([
            'status' => LeaveStatus::Approved,
            'start_date' => self::WORKDAY,
            'end_date' => self::WORKDAY,
        ]);

        $this->actingAs($this->teacher)->get(route('attendance.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canCheckIn', false)
                ->where('canCheckOut', false)
                ->where('blockedReason', 'Anda sedang dalam izin yang disetujui hari ini.'));
    }

    public function test_page_outside_the_window_is_blocked(): void
    {
        $this->travelTo(self::WORKDAY.' 05:00:00');

        $this->actingAs($this->teacher)->get(route('attendance.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canCheckIn', false)
                ->where('blockedReason', 'Absen masuk baru dibuka pukul 05:30.'));
    }

    public function test_page_reports_unset_school_location(): void
    {
        SchoolSetting::current()->update(['latitude' => null, 'longitude' => null]);

        $this->actingAs($this->teacher)->get(route('attendance.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('school.latitude', null)
                ->where('canCheckIn', false)
                ->where('blockedReason', 'Lokasi sekolah belum diatur. Hubungi admin.'));
    }
}
