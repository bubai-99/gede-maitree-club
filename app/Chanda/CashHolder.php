<?php

namespace App\Chanda;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * A collector still holding chanda cash: how much, from how many receipts, since when.
 */
final readonly class CashHolder
{
    public function __construct(
        public User $collector,
        public int $amount,
        public int $receipts,
        public Carbon $since,
    ) {}
}
