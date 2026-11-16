<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PlantLight: string
{
    use HasLabels;

    case Sun = 'sun';
    case Part = 'part';
    case Shade = 'shade';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Sun => 'Full sun',
            self::Part => 'Some sun',
            self::Shade => 'Indoor light',
        };
    }
}
