<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Income (joma) and expense (khoroch) change a book's balance. A deposit moves cash
 * into the bank and a withdrawal moves it back; neither is income or expense.
 */
enum LedgerType: string
{
    use HasLabels;

    case Income = 'income';
    case Expense = 'expense';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Income => 'Joma (in)',
            self::Expense => 'Khoroch (out)',
            self::Deposit => 'Cash put in the bank',
            self::Withdrawal => 'Cash taken from the bank',
        };
    }

    public function isTransfer(): bool
    {
        return $this === self::Deposit || $this === self::Withdrawal;
    }
}
