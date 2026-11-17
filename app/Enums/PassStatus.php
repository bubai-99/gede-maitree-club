<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PassStatus: string
{
    use HasLabels;

    case Active = 'active';
    case Cancelled = 'cancelled';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Cancelled => 'Cancelled',
        };
    }
}
