<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plant>
 */
class PlantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $price = fake()->numberBetween(4, 30) * 10;

        return [
            'slug' => Str::slug($name),
            'name' => ucfirst($name),
            'category' => fake()->randomElement(['flowering', 'seasonal', 'fruit', 'medicinal', 'indoor']),
            'pot' => '6"',
            'price' => $price,
            'member_price' => (int) round($price * 0.9),
            'stock' => fake()->numberBetween(0, 50),
            'light' => 'sun',
            'water' => 'moderate',
            'about' => fake()->sentence(),
            'tip' => fake()->sentence(),
            'active' => true,
            'status' => PublishStatus::Published,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PublishStatus::Draft, 'published_at' => null]);
    }
}
