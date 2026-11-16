<?php

namespace Database\Factories;

use App\Enums\EnquiryKind;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => EnquiryKind::Contact,
            'name' => fake()->name(),
            'phone' => fake()->numerify('98########'),
            'payload' => ['message' => fake()->sentence()],
            'locale' => 'en',
        ];
    }
}
