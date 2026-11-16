<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\CommitteeMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => 'Secretary',
            'since' => 2024,
            'sort' => 1,
            'status' => PublishStatus::Published,
            'published_at' => now(),
        ];
    }
}
