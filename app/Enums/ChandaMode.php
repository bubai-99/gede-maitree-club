<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * How a chanda gift was given: cash to the collector, or UPI to the club's account.
 */
enum ChandaMode: string
{
    use HasLabels;

    case Cash = 'cash';
    case Upi = 'upi';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Upi => 'UPI',
        };
    }

    public function account(): LedgerAccount
    {
        return $this === self::Cash ? LedgerAccount::Cash : LedgerAccount::Bank;
    }
}
