<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PanjikaKind: string
{
    use HasLabels;

    case Puja = 'puja';
    case Parbon = 'parbon';
    case Wedding = 'wedding';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Puja => 'Puja',
            self::Parbon => 'Parbon',
            self::Wedding => 'Wedding day',
        };
    }
}
