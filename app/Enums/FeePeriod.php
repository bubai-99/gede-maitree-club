<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum FeePeriod: string
{
    use HasLabels;

    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }
}
