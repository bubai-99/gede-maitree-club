<?php

namespace Database\Factories;

use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Models\Family;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'amount' => 300,
            'mode' => PaymentMode::Cash,
            'receipt_no' => 'MC-'.now()->format('y').'-'.fake()->unique()->numerify('9###'),
            'status' => PaymentStatus::Confirmed,
            'paid_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
