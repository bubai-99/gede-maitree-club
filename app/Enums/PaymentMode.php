<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PaymentMode: string
{
    use HasLabels;

    case Cash = 'cash';
    case Upi = 'upi';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Upi => 'UPI',
        };
    }
}
