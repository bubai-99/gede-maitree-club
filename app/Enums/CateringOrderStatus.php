<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum CateringOrderStatus: string
{
    use HasLabels;

    case Enquiry = 'enquiry';
    case Quoted = 'quoted';
    case Advance = 'advance';
    case Done = 'done';
    case Settled = 'settled';
    case Lost = 'lost';
    case Cancelled = 'cancelled';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Enquiry => 'Enquiry',
            self::Quoted => 'Quoted',
            self::Advance => 'Advance paid',
            self::Done => 'Served, bill due',
            self::Settled => 'Settled',
            self::Lost => 'Lost',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The five columns of the orders board, in order.
     *
     * @return list<self>
     */
    public static function board(): array
    {
        return [self::Enquiry, self::Quoted, self::Advance, self::Done, self::Settled];
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Settled, self::Lost, self::Cancelled], true);
    }
}
