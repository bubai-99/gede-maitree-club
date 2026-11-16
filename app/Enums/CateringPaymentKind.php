<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum CateringPaymentKind: string
{
    use HasLabels;

    case Advance = 'advance';
    case Final = 'final';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Advance => 'Advance',
            self::Final => 'Final bill',
        };
    }
}
