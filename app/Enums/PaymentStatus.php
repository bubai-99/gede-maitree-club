<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PaymentStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for the treasurer',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
        };
    }
}
