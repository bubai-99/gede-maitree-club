<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PaymentMode: string
{
    use HasLabels;

    case Cash = 'cash';
    case Upi = 'upi';
    case Online = 'online';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Upi => 'UPI',
            self::Online => 'Online',
        };
    }

    /**
     * Where the money lands: cash in hand, or the club's bank account.
     */
    public function account(): LedgerAccount
    {
        return $this === self::Cash ? LedgerAccount::Cash : LedgerAccount::Bank;
    }
}
