<?php

namespace App\Enums;

enum LeaveType: string
{
    case Izin = 'izin';
    case Sakit = 'sakit';
    case Cuti = 'cuti';

    public function label(): string
    {
        return match ($this) {
            self::Izin => 'Izin',
            self::Sakit => 'Sakit',
            self::Cuti => 'Cuti',
        };
    }
}
