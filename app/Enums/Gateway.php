<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * How a member pays in the app: Razorpay Standard Checkout, or the club's own UPI ID
 * with a UTR that the treasurer confirms (used while no Razorpay keys are set).
 */
enum Gateway: string
{
    use HasLabels;

    case Razorpay = 'razorpay';
    case ManualUpi = 'manual_upi';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Razorpay => 'Razorpay',
            self::ManualUpi => 'UPI to the club',
        };
    }
}
