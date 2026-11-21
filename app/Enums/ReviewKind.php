<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum ReviewKind: string
{
    use HasLabels;

    case Catering = 'catering';
    case Nursery = 'nursery';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Catering => 'Maitree Serving',
            self::Nursery => 'Maitree Nursery',
        };
    }
}
