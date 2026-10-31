<?php

namespace Tests\Feature\Guru;

use App\Models\Attendance;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-15 10:00:00');

        foreach (range(0, 6) as $dayOfWeek) {
            WorkSchedule::query()->create([
                'day_of_week' => $dayOfWeek,
                'is_workday' => $dayOfWeek !== 0,
                'starts_at' => '07:00',
                'ends_at' => '14:00',
            ]);
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_shows_not_yet_checked_in_status(): void
    {
        $teacher = User::factory()->guru()->create(['created_at' => '2026-01-01']);

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('today.date', '2026-10-15')
                ->where('today.status', 'belum_absen')
                ->where('today.check_in_at', null)
                ->where('today.check_out_at', null)
                ->where('month.month', 10)
                ->where('summary.hadir', 0));
    }

    public function test_dashboard_shows_todays_times_and_monthly_summary(): void
    {
        $teacher = User::factory()->guru()->create(['created_at' => '2026-01-01']);
        Attendance::factory()->for($teacher)->onDate('2026-10-15')->checkedOut()->create();
        Attendance::factory()->for($teacher)->onDate('2026-10-14')->late()->create();
        Attendance::factory()->for(User::factory()->guru())->onDate('2026-10-13')->create();

        $this->actingAs($teacher)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('today.status', 'hadir')
                ->whereNot('today.check_in_at', null)
                ->whereNot('today.check_out_at', null)
                ->where('summary.hadir', 1)
                ->where('summary.terlambat', 1));
    }
}
