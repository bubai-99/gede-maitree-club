<?php

namespace Database\Factories;

use App\Enums\DueStatus;
use App\Models\Due;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Due>
 */
class DueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'period' => now()->startOfMonth()->subMonths(fake()->unique()->numberBetween(0, 120)),
            'amount' => 100,
            'status' => DueStatus::Due,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['status' => DueStatus::Paid]);
    }
}
