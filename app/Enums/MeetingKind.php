<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum MeetingKind: string
{
    use HasLabels;

    case Committee = 'committee';
    case Agm = 'agm';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Committee => 'Committee meeting',
            self::Agm => 'AGM',
        };
    }
}
