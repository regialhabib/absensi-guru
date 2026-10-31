<?php

namespace Tests\Feature\Admin;

use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $schedules = collect(range(0, 6))->map(fn (int $day): array => [
            'day_of_week' => $day,
            'is_workday' => $day !== 0,
            'starts_at' => $day !== 0 ? '07:00' : null,
            'ends_at' => $day !== 0 ? '14:00' : null,
        ])->all();

        return [
            'name' => 'Pondok Baru',
            'latitude' => -7.72,
            'longitude' => 113.49,
            'radius_meters' => 150,
            'max_accuracy_meters' => 80,
            'late_tolerance_minutes' => 5,
            'check_in_opens_at' => '05:00',
            'check_out_closes_at' => '17:00',
            'schedules' => $schedules,
            ...$overrides,
        ];
    }

    public function test_guest_and_guru_are_refused(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));

        $guru = User::factory()->guru()->create();
        $this->actingAs($guru)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($guru)->put(route('admin.settings.update'), $this->validPayload())->assertForbidden();
    }

    public function test_edit_returns_settings_and_seven_schedules(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/settings/edit')
                ->has('setting')
                ->has('schedules', 7)
                ->where('schedules.3.day_of_week', 3));
    }

    public function test_update_saves_settings_and_all_schedules(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $setting = SchoolSetting::query()->firstOrFail();
        $this->assertSame('Pondok Baru', $setting->name);
        $this->assertSame(150, $setting->radius_meters);
        $this->assertSame(-7.72, $setting->latitude);

        $this->assertSame(7, WorkSchedule::query()->count());
        $this->assertFalse(WorkSchedule::query()->where('day_of_week', 0)->firstOrFail()->is_workday);
        $monday = WorkSchedule::query()->where('day_of_week', 1)->firstOrFail();
        $this->assertTrue($monday->is_workday);
        $this->assertStringStartsWith('07:00', $monday->starts_at);
    }

    public function test_workday_requires_times_and_end_after_start(): void
    {
        $payload = $this->validPayload();
        $payload['schedules'][1]['starts_at'] = null;
        $payload['schedules'][2]['starts_at'] = '10:00';
        $payload['schedules'][2]['ends_at'] = '09:00';

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), $payload)
            ->assertSessionHasErrors(['schedules.1.starts_at', 'schedules.2.ends_at']);
    }

    public function test_non_workday_does_not_require_times_and_clears_them(): void
    {
        $payload = $this->validPayload();
        $payload['schedules'][1]['is_workday'] = false;
        $payload['schedules'][1]['starts_at'] = null;
        $payload['schedules'][1]['ends_at'] = null;

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), $payload)
            ->assertSessionHasNoErrors();

        $monday = WorkSchedule::query()->where('day_of_week', 1)->firstOrFail();
        $this->assertFalse($monday->is_workday);
        $this->assertNull($monday->starts_at);
    }

    public function test_invalid_values_are_rejected_and_nothing_is_saved(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), $this->validPayload(['latitude' => 120, 'radius_meters' => 1]))
            ->assertSessionHasErrors(['latitude', 'radius_meters']);

        $this->assertSame(0, WorkSchedule::query()->count());
    }
}
