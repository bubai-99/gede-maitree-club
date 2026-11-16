<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum LedgerType: string
{
    use HasLabels;

    case Income = 'income';
    case Expense = 'expense';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Income => 'Income',
            self::Expense => 'Expense',
        };
    }
}
