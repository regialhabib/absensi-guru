<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'between:10,5000'],
            'max_accuracy_meters' => ['required', 'integer', 'between:10,1000'],
            'late_tolerance_minutes' => ['required', 'integer', 'between:0,180'],
            'check_in_opens_at' => ['required', 'date_format:H:i'],
            'check_out_closes_at' => ['required', 'date_format:H:i'],
            'schedules' => ['required', 'array', 'size:7'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'schedules.*.is_workday' => ['required', 'boolean'],
        ];

        foreach (range(0, 6) as $index) {
            if ($this->boolean("schedules.{$index}.is_workday")) {
                $rules["schedules.{$index}.starts_at"] = ['required', 'date_format:H:i'];
                $rules["schedules.{$index}.ends_at"] = ['required', 'date_format:H:i', "after:schedules.{$index}.starts_at"];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lembaga',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'radius_meters' => 'radius',
            'max_accuracy_meters' => 'akurasi maksimum',
            'late_tolerance_minutes' => 'toleransi keterlambatan',
            'check_in_opens_at' => 'jam pembukaan absen masuk',
            'check_out_closes_at' => 'jam penutupan absen pulang',
            'schedules.*.starts_at' => 'jam masuk',
            'schedules.*.ends_at' => 'jam pulang',
        ];
    }
}
