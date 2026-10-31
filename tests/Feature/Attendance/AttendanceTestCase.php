<?php

namespace Tests\Feature\Attendance;

use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class AttendanceTestCase extends TestCase
{
    use RefreshDatabase;

    /** A Monday. */
    protected const string WORKDAY = '2026-11-02';

    /** A Sunday. */
    protected const string SUNDAY = '2026-11-01';

    protected const float SCHOOL_LATITUDE = -7.72;

    protected const float SCHOOL_LONGITUDE = 113.49;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->travelTo(self::WORKDAY.' 06:50:00');

        SchoolSetting::current()->update([
            'latitude' => self::SCHOOL_LATITUDE,
            'longitude' => self::SCHOOL_LONGITUDE,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'late_tolerance_minutes' => 10,
            'check_in_opens_at' => '05:30:00',
            'check_out_closes_at' => '18:00:00',
        ]);

        foreach (range(0, 6) as $dayOfWeek) {
            WorkSchedule::query()->create([
                'day_of_week' => $dayOfWeek,
                'is_workday' => $dayOfWeek !== 0,
                'starts_at' => $dayOfWeek !== 0 ? '07:00:00' : null,
                'ends_at' => $dayOfWeek !== 0 ? '14:00:00' : null,
            ]);
        }

        $this->teacher = User::factory()->guru()->create(['created_at' => '2026-01-01 08:00:00']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'latitude' => self::SCHOOL_LATITUDE,
            'longitude' => self::SCHOOL_LONGITUDE,
            'accuracy' => 12,
            'face_detected' => 1,
            ...$overrides,
        ];
    }

    protected function checkIn(array $overrides = [], ?User $user = null)
    {
        return $this->actingAs($user ?? $this->teacher)
            ->post(route('attendance.check-in'), $this->payload($overrides));
    }

    protected function checkOut(array $overrides = [], ?User $user = null)
    {
        return $this->actingAs($user ?? $this->teacher)
            ->post(route('attendance.check-out'), $this->payload($overrides));
    }
}
