<?php

namespace Database\Factories;

use App\Enums\FamilyStatus;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_no' => 'MC-'.fake()->unique()->numerify('9###'),
            'head_name' => fake()->name(),
            'address' => fake()->streetAddress(),
            'area' => fake()->randomElement(['Lane 1', 'Lane 2', 'Lane 3', 'Pukur Par', 'School Road']),
            'phone' => fake()->unique()->numerify('98########'),
            'status' => FamilyStatus::Active,
            'joined_on' => fake()->dateTimeBetween('-10 years', '-1 month'),
        ];
    }
}
