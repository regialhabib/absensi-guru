<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  array{institution: string, period: string, rows: list<array<string, mixed>>}  $recap
     */
    public function __construct(private array $recap) {}

    /**
     * @return list<list<string|int|null>>
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->recap['rows'] as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['name'],
                $row['nip'],
                $row['hadir'],
                $row['terlambat'],
                $row['izin'],
                $row['sakit'],
                $row['cuti'],
                $row['alpa'],
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    public function headings(): array
    {
        return [
            [$this->recap['institution']],
            ['Rekap Absensi Guru - '.$this->recap['period']],
            [],
            ['No', 'Nama', 'NIP', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Cuti', 'Alpa'],
        ];
    }

    public function title(): string
    {
        return 'Rekap Absensi';
    }
}
