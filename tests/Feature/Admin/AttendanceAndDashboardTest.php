<?php

namespace Tests\Feature\Admin;

use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 3, 11)->setTime(10, 0));

        foreach (range(0, 6) as $day) {
            WorkSchedule::query()->create([
                'day_of_week' => $day,
                'is_workday' => true,
                'starts_at' => '07:00:00',
                'ends_at' => '14:00:00',
            ]);
        }
    }

    public function test_guest_and_guru_are_refused(): void
    {
        $this->get(route('admin.attendances.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $guru = User::factory()->guru()->create();
        $this->actingAs($guru)->get(route('admin.attendances.index'))->assertForbidden();
        $this->actingAs($guru)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_attendance_defaults_to_today_and_filters_by_date_and_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $first = User::factory()->guru()->create();
        $second = User::factory()->guru()->create();

        Attendance::factory()->for($first)->create();
        Attendance::factory()->for($second)->create();
        Attendance::factory()->for($first)->onDate('2026-03-10')->create();

        $this->actingAs($admin)
            ->get(route('admin.attendances.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/attendances/index')
                ->where('filters.date', '2026-03-11')
                ->has('attendances.data', 2)
                ->has('attendances.data.0.user'));

        $this->actingAs($admin)
            ->get(route('admin.attendances.index', ['date' => '2026-03-10']))
            ->assertInertia(fn ($page) => $page->has('attendances.data', 1));

        $this->actingAs($admin)
            ->get(route('admin.attendances.index', ['user_id' => $second->id]))
            ->assertInertia(fn ($page) => $page
                ->has('attendances.data', 1)
                ->where('attendances.data.0.user_id', $second->id));
    }

    public function test_attendance_rejects_invalid_date(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.attendances.index', ['date' => 'bukan-tanggal']))
            ->assertSessionHasErrors('date');
    }

    public function test_dashboard_counts_today_and_pending_leaves(): void
    {
        $admin = User::factory()->admin()->create();
        $present = User::factory()->guru()->create(['created_at' => now()->subMonth()]);
        $late = User::factory()->guru()->create(['created_at' => now()->subMonth()]);
        $onLeave = User::factory()->guru()->create(['created_at' => now()->subMonth()]);
        User::factory()->guru()->create(['created_at' => now()->subMonth()]);
        User::factory()->guru()->inactive()->create(['created_at' => now()->subMonth()]);

        Attendance::factory()->for($present)->create();
        Attendance::factory()->for($late)->late()->create();
        LeaveRequest::factory()->for($onLeave)->approved($admin)->forDates('2026-03-11', '2026-03-11')->create();
        LeaveRequest::factory()->count(2)->for($present)->create(['status' => LeaveStatus::Pending]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->where('summary.hadir', 1)
                ->where('summary.terlambat', 1)
                ->where('summary.izin', 1)
                ->where('summary.belum_absen', 1)
                ->where('summary.total', 4)
                ->where('pendingLeaves', 2));
    }
}
