<?php

namespace Tests\Feature\Services\Attendance;

use App\Enums\DayStatus;
use App\Enums\LeaveType;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\Attendance\DailyStatusResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DailyStatusResolverTest extends TestCase
{
    use RefreshDatabase;

    private const string TODAY = '2026-10-15'; // Thursday

    private const string PAST_WORKDAY = '2026-10-05'; // Monday

    private const string SUNDAY = '2026-10-04';

    private DailyStatusResolver $resolver;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(self::TODAY.' 10:00:00');

        foreach (range(0, 6) as $dayOfWeek) {
            WorkSchedule::query()->create([
                'day_of_week' => $dayOfWeek,
                'is_workday' => $dayOfWeek !== 0,
                'starts_at' => '07:00',
                'ends_at' => '14:00',
            ]);
        }

        $this->resolver = new DailyStatusResolver;
        $this->teacher = User::factory()->guru()->create(['created_at' => '2026-01-01 08:00:00']);
    }

    private function statusOn(string $date): DayStatus
    {
        return $this->resolver->resolve($this->teacher, CarbonImmutable::parse($date));
    }

    public function test_on_time_attendance_resolves_to_hadir(): void
    {
        Attendance::factory()->for($this->teacher)->onDate(self::PAST_WORKDAY)->create();

        $this->assertSame(DayStatus::Hadir, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_late_attendance_resolves_to_terlambat(): void
    {
        Attendance::factory()->for($this->teacher)->late()->onDate(self::PAST_WORKDAY)->create();

        $this->assertSame(DayStatus::Terlambat, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_attendance_on_a_non_workday_still_counts_as_present(): void
    {
        Attendance::factory()->for($this->teacher)->onDate(self::SUNDAY)->create();

        $this->assertSame(DayStatus::Hadir, $this->statusOn(self::SUNDAY));
    }

    public function test_attendance_takes_precedence_over_approved_leave(): void
    {
        Attendance::factory()->for($this->teacher)->onDate(self::PAST_WORKDAY)->create();
        LeaveRequest::factory()->for($this->teacher)->approved()
            ->forDates(self::PAST_WORKDAY, self::PAST_WORKDAY)->create();

        $this->assertSame(DayStatus::Hadir, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_non_workday_without_attendance_resolves_to_libur(): void
    {
        $this->assertSame(DayStatus::Libur, $this->statusOn(self::SUNDAY));
    }

    public function test_weekday_without_a_schedule_row_resolves_to_libur(): void
    {
        WorkSchedule::query()->where('day_of_week', 1)->delete();

        $this->assertSame(DayStatus::Libur, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_approved_leave_on_a_non_workday_resolves_to_libur(): void
    {
        LeaveRequest::factory()->for($this->teacher)->approved()
            ->forDates(self::SUNDAY, self::SUNDAY)->create();

        $this->assertSame(DayStatus::Libur, $this->statusOn(self::SUNDAY));
    }

    #[DataProvider('leaveTypes')]
    public function test_approved_leave_resolves_to_its_type(LeaveType $type, DayStatus $expected): void
    {
        LeaveRequest::factory()->for($this->teacher)->approved()
            ->forDates(self::PAST_WORKDAY, self::PAST_WORKDAY)->create(['type' => $type]);

        $this->assertSame($expected, $this->statusOn(self::PAST_WORKDAY));
    }

    /**
     * @return array<string, array{LeaveType, DayStatus}>
     */
    public static function leaveTypes(): array
    {
        return [
            'izin' => [LeaveType::Izin, DayStatus::Izin],
            'sakit' => [LeaveType::Sakit, DayStatus::Sakit],
            'cuti' => [LeaveType::Cuti, DayStatus::Cuti],
        ];
    }

    public function test_pending_leave_does_not_excuse_an_absence(): void
    {
        LeaveRequest::factory()->for($this->teacher)
            ->forDates(self::PAST_WORKDAY, self::PAST_WORKDAY)->create();

        $this->assertSame(DayStatus::Alpa, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_rejected_leave_does_not_excuse_an_absence(): void
    {
        LeaveRequest::factory()->for($this->teacher)->rejected()
            ->forDates(self::PAST_WORKDAY, self::PAST_WORKDAY)->create();

        $this->assertSame(DayStatus::Alpa, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_another_teachers_records_do_not_affect_the_status(): void
    {
        $colleague = User::factory()->guru()->create();
        Attendance::factory()->for($colleague)->onDate(self::PAST_WORKDAY)->create();

        $this->assertSame(DayStatus::Alpa, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_past_workday_without_records_resolves_to_alpa(): void
    {
        $this->assertSame(DayStatus::Alpa, $this->statusOn(self::PAST_WORKDAY));
    }

    public function test_today_without_records_resolves_to_belum_absen(): void
    {
        $this->assertSame(DayStatus::BelumAbsen, $this->statusOn(self::TODAY));
    }

    public function test_future_workday_resolves_to_belum_absen(): void
    {
        $this->assertSame(DayStatus::BelumAbsen, $this->statusOn('2026-10-16'));
    }

    public function test_workday_before_the_account_existed_is_not_alpa(): void
    {
        $this->teacher->forceFill(['created_at' => '2026-10-06 09:00:00'])->save();

        $this->assertSame(DayStatus::BelumAbsen, $this->statusOn(self::PAST_WORKDAY));
        $this->assertSame(DayStatus::Alpa, $this->statusOn('2026-10-07'));
    }

    public function test_month_includes_every_day_and_records_on_its_last_day(): void
    {
        Attendance::factory()->for($this->teacher)->onDate('2026-09-30')->create();

        $month = $this->resolver->resolveMonth($this->teacher, 2026, 9);

        $this->assertCount(30, $month);
        $this->assertSame('2026-09-01', array_key_first($month));
        $this->assertSame('2026-09-30', array_key_last($month));
        $this->assertSame(DayStatus::Hadir, $month['2026-09-30']);
    }

    public function test_leave_crossing_a_month_boundary_applies_to_the_days_inside_the_month(): void
    {
        LeaveRequest::factory()->for($this->teacher)->approved()
            ->forDates('2026-09-29', '2026-10-02')->create(['type' => LeaveType::Sakit]);

        $month = $this->resolver->resolveMonth($this->teacher, 2026, 10);

        $this->assertSame(DayStatus::Sakit, $month['2026-10-01']);
        $this->assertSame(DayStatus::Sakit, $month['2026-10-02']);
        $this->assertSame(DayStatus::Alpa, $month['2026-10-03']);
    }

    public function test_summary_counts_each_status_and_skips_libur_and_belum_absen(): void
    {
        Attendance::factory()->for($this->teacher)->onDate('2026-10-01')->create();
        Attendance::factory()->for($this->teacher)->late()->onDate('2026-10-02')->create();
        LeaveRequest::factory()->for($this->teacher)->approved()
            ->forDates('2026-10-03', '2026-10-03')->create(['type' => LeaveType::Izin]);
        LeaveRequest::factory()->for($this->teacher)->approved()
            ->forDates('2026-10-05', '2026-10-05')->create(['type' => LeaveType::Sakit]);
        LeaveRequest::factory()->for($this->teacher)->approved()
            ->forDates('2026-10-06', '2026-10-07')->create(['type' => LeaveType::Cuti]);

        $this->assertSame([
            'hadir' => 1,
            'terlambat' => 1,
            'izin' => 1,
            'sakit' => 1,
            'cuti' => 2,
            'alpa' => 6,
        ], $this->resolver->summarize($this->teacher, 2026, 10));
    }
}
