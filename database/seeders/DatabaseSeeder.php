<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedSchoolSetting();
        $this->seedWorkSchedules();

        if (app()->environment('local')) {
            User::factory()->guru()->count(5)->create();
        }
    }

    private function seedAdmin(): void
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (blank($password)) {
            throw new RuntimeException('ADMIN_PASSWORD belum diisi di .env, seeder admin dibatalkan.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => $password,
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    private function seedSchoolSetting(): void
    {
        SchoolSetting::query()->firstOrCreate([], [
            'name' => 'Pondok Pesantren Riyadhul Amien',
            'latitude' => null,
            'longitude' => null,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'late_tolerance_minutes' => 10,
            'check_in_opens_at' => '05:30:00',
            'check_out_closes_at' => '18:00:00',
        ]);
    }

    private function seedWorkSchedules(): void
    {
        foreach (range(0, 6) as $day) {
            $workday = $day !== 0;

            WorkSchedule::query()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'is_workday' => $workday,
                    'starts_at' => $workday ? '07:00:00' : null,
                    'ends_at' => $workday ? '14:00:00' : null,
                ],
            );
        }
    }
}
