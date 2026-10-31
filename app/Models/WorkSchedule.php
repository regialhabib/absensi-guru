<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $day_of_week
 * @property bool $is_workday
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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
