<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum CateringPaymentKind: string
{
    use HasLabels;

    case Advance = 'advance';
    case Part = 'part';
    case Final = 'final';
    case Refund = 'refund';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Advance => 'Advance',
            self::Part => 'Part payment',
            self::Final => 'Final bill',
            self::Refund => 'Refund',
        };
    }
}
