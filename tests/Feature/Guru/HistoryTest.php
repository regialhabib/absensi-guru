<?php

namespace Tests\Feature\Guru;

use App\Models\Attendance;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

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

        $this->teacher = User::factory()->guru()->create(['created_at' => '2026-01-01']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('attendance.history'))->assertRedirect(route('login'));
    }

    public function test_defaults_to_the_current_month(): void
    {
        Attendance::factory()->for($this->teacher)->onDate('2026-10-05')->checkedOut()->create();

        $this->actingAs($this->teacher)
            ->get(route('attendance.history'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('attendance/history')
                ->where('year', 2026)
                ->where('month', 10)
                ->has('days', 31)
                ->where('days.4.date', '2026-10-05')
                ->where('days.4.status', 'hadir')
                ->where('summary.hadir', 1));
    }

    public function test_shows_the_requested_month(): void
    {
        Attendance::factory()->for($this->teacher)->onDate('2026-09-07')->late(20)->create();

        $this->actingAs($this->teacher)
            ->get(route('attendance.history', ['year' => 2026, 'month' => 9]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('month', 9)
                ->has('days', 30)
                ->where('days.6.status', 'terlambat')
                ->where('days.6.late_minutes', 20)
                ->where('summary.terlambat', 1));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPeriods(): array
    {
        return [
            'month too high' => [['month' => 13], 'month'],
            'month zero' => [['month' => 0], 'month'],
            'month text' => [['month' => 'abc'], 'month'],
            'year too low' => [['year' => 1], 'year'],
            'year text' => [['year' => 'abc'], 'year'],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[DataProvider('invalidPeriods')]
    public function test_rejects_invalid_period(array $query, string $field): void
    {
        $this->actingAs($this->teacher)
            ->get(route('attendance.history', $query))
            ->assertSessionHasErrors($field);
    }

    public function test_teachers_only_see_their_own_data(): void
    {
        $other = User::factory()->guru()->create(['created_at' => '2026-01-01']);
        Attendance::factory()->for($other)->onDate('2026-10-05')->create();

        $this->actingAs($this->teacher)
            ->get(route('attendance.history'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.4.status', 'alpa')
                ->where('days.4.check_in_at', null)
                ->where('summary.hadir', 0));
    }
}
