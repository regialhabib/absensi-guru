<?php

namespace App\Enums;

enum DayStatus: string
{
    case Hadir = 'hadir';
    case Terlambat = 'terlambat';
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Cuti = 'cuti';
    case Alpa = 'alpa';
    case Libur = 'libur';
    case BelumAbsen = 'belum_absen';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Terlambat => 'Terlambat',
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::Cuti => 'Cuti',
            self::Alpa => 'Alpa',
            self::Libur => 'Libur',
            self::BelumAbsen => 'Belum Absen',
        };
    }
}
