<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum FamilyStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Active = 'active';
    case Lapsed = 'lapsed';
    case Left = 'left';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending approval',
            self::Active => 'Active',
            self::Lapsed => 'Lapsed',
            self::Left => 'Left',
        };
    }
}
