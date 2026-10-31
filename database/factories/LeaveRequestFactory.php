<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 14))->startOfDay();

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(LeaveType::cases()),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(fake()->numberBetween(0, 2))->toDateString(),
            'reason' => fake()->sentence(),
            'attachment' => null,
            'status' => LeaveStatus::Pending,
        ];
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Approved,
            'reviewed_by' => $reviewer instanceof User ? $reviewer->id : User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Rejected,
            'reviewed_by' => $reviewer instanceof User ? $reviewer->id : User::factory()->admin(),
            'reviewed_at' => now(),
            'review_note' => 'Ditolak.',
        ]);
    }

    public function forDates(string $start, string $end): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
