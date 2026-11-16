<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PlantWater: string
{
    use HasLabels;

    case Often = 'often';
    case Moderate = 'moderate';
    case Little = 'little';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Often => 'Water daily',
            self::Moderate => 'Every 2–3 days',
            self::Little => 'Once a week',
        };
    }
}
