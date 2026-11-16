<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->name(),
            'relation' => fake()->randomElement(['Spouse', 'Son', 'Daughter', 'Mother', 'Father']),
            'is_head' => false,
            'dob' => fake()->dateTimeBetween('-80 years', '-2 years'),
            'blood_group' => fake()->randomElement(Member::BLOOD_GROUPS),
        ];
    }

    public function head(): static
    {
        return $this->state(fn (array $attributes) => [
            'relation' => 'Head of family',
            'is_head' => true,
        ]);
    }
}
