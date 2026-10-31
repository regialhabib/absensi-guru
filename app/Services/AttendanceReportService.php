<?php

namespace App\Services;

use App\Enums\DayStatus;
use App\Enums\UserRole;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\Attendance\DailyStatusResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    public function __construct(private DailyStatusResolver $resolver) {}

    /**
     * Today's status counts across all active teachers.
     *
     * @return array{total: int, hadir: int, terlambat: int, izin: int, belum_absen: int}
     */
    public function todaySummary(): array
    {
        $counts = $this->activeTeachers()
            ->countBy(fn (User $teacher): string => $this->resolver->resolve($teacher, today())->value);

        return [
            'total' => $counts->sum(),
            'hadir' => $counts->get(DayStatus::Hadir->value, 0),
            'terlambat' => $counts->get(DayStatus::Terlambat->value, 0),
            'izin' => $counts->get(DayStatus::Izin->value, 0)
                + $counts->get(DayStatus::Sakit->value, 0)
                + $counts->get(DayStatus::Cuti->value, 0),
            'belum_absen' => $counts->get(DayStatus::BelumAbsen->value, 0),
        ];
    }

    /**
     * Monthly recap per active teacher.
     *
     * @return array{
     *     institution: string,
     *     year: int,
     *     month: int,
     *     period: string,
     *     rows: list<array{id: int, name: string, nip: string|null, hadir: int, terlambat: int, izin: int, sakit: int, cuti: int, alpa: int}>
     * }
     */
    public function monthlyRecap(int $year, int $month): array
    {
        $rows = $this->activeTeachers()
            ->map(fn (User $teacher): array => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'nip' => $teacher->nip,
                ...$this->resolver->summarize($teacher, $year, $month),
            ])
            ->all();

        return [
            'institution' => SchoolSetting::current()->name,
            'year' => $year,
            'month' => $month,
            'period' => CarbonImmutable::create($year, $month, 1)->settings(['locale' => 'id'])->translatedFormat('F Y'),
            'rows' => array_values($rows),
        ];
    }

    public function fileName(int $year, int $month, string $extension): string
    {
        return sprintf('rekap-absensi-%04d-%02d.%s', $year, $month, $extension);
    }

    /**
     * @return Collection<int, User>
     */
    private function activeTeachers(): Collection
    {
        return User::query()
            ->where('role', UserRole::Guru)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
