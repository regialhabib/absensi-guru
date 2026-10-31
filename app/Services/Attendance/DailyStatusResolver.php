<?php

namespace App\Services\Attendance;

use App\Enums\DayStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class DailyStatusResolver
{
    /**
     * The attendance status of one teacher on one date.
     *
     * Precedence: attendance record (hadir/terlambat) > non-workday (libur) >
     * approved leave (izin/sakit/cuti) > before the account existed or
     * today/future (belum_absen) > past workday without a record (alpa).
     */
    public function resolve(User $user, CarbonInterface $date): DayStatus
    {
        $day = CarbonImmutable::parse($date->toDateString());

        return $this->resolveRange($user, $day, $day)[$day->toDateString()];
    }

    /**
     * The status for every day of the month, keyed by 'Y-m-d'.
     *
     * @return array<string, DayStatus>
     */
    public function resolveMonth(User $user, int $year, int $month): array
    {
        $firstDay = CarbonImmutable::create($year, $month, 1);

        return $this->resolveRange($user, $firstDay, $firstDay->endOfMonth()->startOfDay());
    }

    /**
     * Counts per status over the month (libur and belum_absen are not counted).
     *
     * @return array{hadir:int,terlambat:int,izin:int,sakit:int,cuti:int,alpa:int}
     */
    public function summarize(User $user, int $year, int $month): array
    {
        $counts = collect($this->resolveMonth($user, $year, $month))
            ->countBy(fn (DayStatus $status): string => $status->value);

        return [
            'hadir' => $counts->get(DayStatus::Hadir->value, 0),
            'terlambat' => $counts->get(DayStatus::Terlambat->value, 0),
            'izin' => $counts->get(DayStatus::Izin->value, 0),
            'sakit' => $counts->get(DayStatus::Sakit->value, 0),
            'cuti' => $counts->get(DayStatus::Cuti->value, 0),
            'alpa' => $counts->get(DayStatus::Alpa->value, 0),
        ];
    }

    /**
     * @return array<string, DayStatus>
     */
    private function resolveRange(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $attendances = $user->attendances()
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->get()
            ->keyBy(fn (Attendance $attendance): string => $attendance->date->toDateString());

        $approvedLeaves = $user->leaveRequests()
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get();

        $schedules = WorkSchedule::query()->get()->keyBy('day_of_week');

        $statuses = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $statuses[$day->toDateString()] = $this->statusFor($user, $day, $attendances, $approvedLeaves, $schedules);
        }

        return $statuses;
    }

    /**
     * @param  Collection<string, Attendance>  $attendances
     * @param  Collection<int, LeaveRequest>  $approvedLeaves
     * @param  Collection<int, WorkSchedule>  $schedules
     */
    private function statusFor(
        User $user,
        CarbonInterface $day,
        Collection $attendances,
        Collection $approvedLeaves,
        Collection $schedules,
    ): DayStatus {
        $date = $day->toDateString();

        $attendance = $attendances->get($date);

        if ($attendance !== null) {
            return DayStatus::from($attendance->status->value);
        }

        if (! $schedules->get($day->dayOfWeek)?->is_workday) {
            return DayStatus::Libur;
        }

        $leave = $approvedLeaves->first(
            fn (LeaveRequest $leave): bool => $leave->start_date->toDateString() <= $date
                && $leave->end_date->toDateString() >= $date,
        );

        if ($leave !== null) {
            return DayStatus::from($leave->type->value);
        }

        if ($date < $user->created_at->toDateString() || $date >= today()->toDateString()) {
            return DayStatus::BelumAbsen;
        }

        return DayStatus::Alpa;
    }
}
