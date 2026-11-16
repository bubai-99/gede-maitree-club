<?php

namespace Database\Factories;

use App\Enums\EventKind;
use App\Enums\PublishStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($title),
            'title' => rtrim($title, '.'),
            'kind' => fake()->randomElement(EventKind::cases()),
            'starts_at' => fake()->dateTimeBetween('+1 day', '+3 months'),
            'time_label' => '9 am – 2 pm',
            'place' => 'Club room',
            'pass_required' => false,
            'contact_role' => 'Secretary',
            'about' => fake()->paragraph(),
            'schedule' => [['time' => '9:00 am', 'time_bn' => '৯:০০', 'what' => 'Doors open', 'what_bn' => 'শুরু']],
            'bring' => [['text' => 'A water bottle', 'text_bn' => 'জলের বোতল']],
            'status' => PublishStatus::Published,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PublishStatus::Draft, 'published_at' => null]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => ['starts_at' => fake()->dateTimeBetween('-1 year', '-2 days')]);
    }
}
