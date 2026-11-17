<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum SponsorStatus: string
{
    use HasLabels;

    case Promised = 'promised';
    case Received = 'received';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Promised => 'Promised',
            self::Received => 'Received',
        };
    }
}
