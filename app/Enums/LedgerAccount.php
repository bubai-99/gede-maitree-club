<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Where a khata entry's money is: cash in hand or the bank account.
 */
enum LedgerAccount: string
{
    use HasLabels;

    case Cash = 'cash';
    case Bank = 'bank';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Bank => 'Bank',
        };
    }
}
