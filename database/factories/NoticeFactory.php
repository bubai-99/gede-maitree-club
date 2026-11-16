<?php

namespace Database\Factories;

use App\Enums\NoticeAudience;
use App\Enums\PublishStatus;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notice>
 */
class NoticeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(5), '.');

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'tag' => 'General',
            'summary' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'audience' => NoticeAudience::Public,
            'pinned' => false,
            'status' => PublishStatus::Published,
            'published_at' => fake()->dateTimeBetween('-2 months'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PublishStatus::Draft, 'published_at' => null]);
    }

    public function membersOnly(): static
    {
        return $this->state(fn (array $attributes) => ['audience' => NoticeAudience::Members]);
    }
}
