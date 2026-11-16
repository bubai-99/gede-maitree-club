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

    public function englishLabel(): string
    {
        return match ($this) {
            self::Enquiry => 'Enquiry',
            self::Quoted => 'Quoted',
            self::Advance => 'Advance paid',
            self::Done => 'Served, bill due',
            self::Settled => 'Settled',
        };
    }
}
