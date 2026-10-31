<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = now()->startOfDay();

        return [
            'user_id' => User::factory(),
            'date' => $date->toDateString(),
            'check_in_at' => $date->copy()->setTime(6, 55),
            'check_in_latitude' => -7.7200000,
            'check_in_longitude' => 113.4900000,
            'check_in_accuracy' => 15.00,
            'check_in_distance' => 20.00,
            'check_in_photo' => 'attendance-photos/sample.jpg',
            'check_in_face_detected' => true,
            'status' => AttendanceStatus::Hadir,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ];
    }

    public function late(int $minutes = 15): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::Terlambat,
            'late_minutes' => $minutes,
            'check_in_at' => now()->startOfDay()->setTime(7, 0)->addMinutes($minutes),
        ]);
    }

    public function checkedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_out_at' => now()->startOfDay()->setTime(14, 5),
            'check_out_latitude' => -7.7200000,
            'check_out_longitude' => 113.4900000,
            'check_out_accuracy' => 12.00,
            'check_out_distance' => 18.00,
            'check_out_photo' => 'attendance-photos/sample-out.jpg',
            'check_out_face_detected' => true,
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
            'check_in_at' => Carbon::parse($date)->setTime(6, 55),
        ]);
    }
}
