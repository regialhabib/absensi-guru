<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_workday' => 'boolean',
        ];
    }

    /**
     * The schedule row for the weekday of the given date (0 = Sunday .. 6 = Saturday).
     */
    public static function forDate(CarbonInterface $date): ?self
    {
        return static::query()->where('day_of_week', $date->dayOfWeek)->first();
    }
}
