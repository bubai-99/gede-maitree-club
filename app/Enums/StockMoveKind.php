<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum StockMoveKind: string
{
    use HasLabels;

    case In = 'in';
    case Sale = 'sale';
    case Loss = 'loss';
    case Adjust = 'adjust';

    public function englishLabel(): string
    {
        return match ($this) {
            self::In => 'Stock in',
            self::Sale => 'Sale',
            self::Loss => 'Loss',
            self::Adjust => 'Adjustment',
        };
    }
}
