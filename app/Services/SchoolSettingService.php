<?php

namespace App\Services;

use App\Models\SchoolSetting;
use App\Models\WorkSchedule;
use Illuminate\Support\Facades\DB;

class SchoolSettingService
{
    /**
     * Current settings plus one schedule per weekday (0 = Sunday .. 6 = Saturday).
     *
     * @return array{
     *     setting: array<string, mixed>,
     *     schedules: list<array{day_of_week: int, is_workday: bool, starts_at: string|null, ends_at: string|null}>
     * }
     */
    public function get(): array
    {
        $setting = SchoolSetting::current();
        $schedules = WorkSchedule::query()->get()->keyBy('day_of_week');

        return [
            'setting' => [
                'name' => $setting->name,
                'latitude' => $setting->latitude,
                'longitude' => $setting->longitude,
                'radius_meters' => $setting->radius_meters,
                'max_accuracy_meters' => $setting->max_accuracy_meters,
                'late_tolerance_minutes' => $setting->late_tolerance_minutes,
                'check_in_opens_at' => $this->trimTime($setting->check_in_opens_at),
                'check_out_closes_at' => $this->trimTime($setting->check_out_closes_at),
            ],
            'schedules' => array_map(fn (int $day): array => [
                'day_of_week' => $day,
                'is_workday' => (bool) $schedules->get($day)?->is_workday,
                'starts_at' => $this->trimTime($schedules->get($day)?->starts_at),
                'ends_at' => $this->trimTime($schedules->get($day)?->ends_at),
            ], range(0, 6)),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<array{day_of_week: int, is_workday: bool, starts_at?: string|null, ends_at?: string|null}>  $schedules
     */
    public function update(array $settings, array $schedules): void
    {
        DB::transaction(function () use ($settings, $schedules): void {
            SchoolSetting::current()->update($settings);

            foreach ($schedules as $schedule) {
                $isWorkday = (bool) $schedule['is_workday'];

                WorkSchedule::query()->updateOrCreate(
                    ['day_of_week' => $schedule['day_of_week']],
                    [
                        'is_workday' => $isWorkday,
                        'starts_at' => $isWorkday ? ($schedule['starts_at'] ?? null) : null,
                        'ends_at' => $isWorkday ? ($schedule['ends_at'] ?? null) : null,
                    ],
                );
            }
        });
    }

    private function trimTime(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}
