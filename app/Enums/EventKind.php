<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum EventKind: string
{
    use HasLabels;

    case Puja = 'puja';
    case Sports = 'sports';
    case Service = 'service';
    case Social = 'social';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Puja => 'Puja',
            self::Sports => 'Sports',
            self::Service => 'Service',
            self::Social => 'Members',
        };
    }
}
