<?php

namespace App\Services\Attendance;

use App\Enums\DayStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use RuntimeException;

class DailyStatusResolver
{
    /**
     * The attendance status of one teacher on one date.
     *
     * Precedence: approved leave (izin/sakit/cuti) > attendance record
     * (hadir/terlambat) > non-workday (libur) > past workday without any
     * record (alpa) > today/future without a record (belum_absen).
     */
    public function resolve(User $user, CarbonInterface $date): DayStatus
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * The status for every day of the month, keyed by 'Y-m-d'.
     *
     * @return array<string, DayStatus>
     */
    public function resolveMonth(User $user, int $year, int $month): array
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Counts per status over the month (libur and belum_absen are not counted).
     *
     * @return array{hadir:int,terlambat:int,izin:int,sakit:int,cuti:int,alpa:int}
     */
    public function summarize(User $user, int $year, int $month): array
    {
        throw new RuntimeException('Not implemented');
    }
}
