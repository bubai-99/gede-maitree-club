<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\CateringPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CateringPackage>
 */
class CateringPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => ucfirst($name),
            'for' => 'Family occasions',
            'rate' => 50,
            'min_guests' => 50,
            'popular' => false,
            'menu' => ['Serving team in club uniform', 'A team leader on site'],
            'sort' => 1,
            'status' => PublishStatus::Published,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PublishStatus::Draft, 'published_at' => null]);
    }
}
