<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum DueStatus: string
{
    use HasLabels;

    case Due = 'due';
    case Paid = 'paid';
    case Waived = 'waived';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Due => 'Due',
            self::Paid => 'Paid',
            self::Waived => 'Waived',
        };
    }
}
