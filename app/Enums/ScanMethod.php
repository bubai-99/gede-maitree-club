<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * How the family was found at the gate: the pass QR, a member card QR, or the family number typed in.
 */
enum ScanMethod: string
{
    use HasLabels;

    case Pass = 'pass';
    case Card = 'card';
    case Typed = 'typed';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Pass => 'Pass QR',
            self::Card => 'Member card',
            self::Typed => 'Family number typed',
        };
    }
}
