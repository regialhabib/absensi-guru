<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
            'max_accuracy_meters' => 'integer',
            'late_tolerance_minutes' => 'integer',
        ];
    }

    /**
     * The single settings row (created with defaults when missing).
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'name' => config('app.name'),
            'check_in_opens_at' => '05:30:00',
            'check_out_closes_at' => '18:00:00',
        ]);
    }
}
