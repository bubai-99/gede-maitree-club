<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PlantCategory: string
{
    use HasLabels;

    case Flowering = 'flowering';
    case Seasonal = 'seasonal';
    case Fruit = 'fruit';
    case Medicinal = 'medicinal';
    case Indoor = 'indoor';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Flowering => 'Flowering',
            self::Seasonal => 'Winter seasonal',
            self::Fruit => 'Fruit',
            self::Medicinal => 'Medicinal',
            self::Indoor => 'Indoor',
        };
    }
}
