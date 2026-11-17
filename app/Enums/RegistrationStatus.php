<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum RegistrationStatus: string
{
    use HasLabels;

    case Registered = 'registered';
    case Cancelled = 'cancelled';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::Cancelled => 'Cancelled',
        };
    }
}
