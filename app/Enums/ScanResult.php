<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * What the gate scanner decided. Only "allowed" lets people in; "already" means the pass was
 * used before (the volunteer may still let someone back in).
 */
enum ScanResult: string
{
    use HasLabels;

    case Allowed = 'allowed';
    case Already = 'already';
    case NoPass = 'no_pass';
    case Cancelled = 'cancelled';
    case WrongEvent = 'wrong_event';
    case Invalid = 'invalid';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Allowed => 'Let in',
            self::Already => 'Already inside',
            self::NoPass => 'No pass for this event',
            self::Cancelled => 'Pass cancelled',
            self::WrongEvent => 'Pass for another event',
            self::Invalid => 'Pass not found',
        };
    }

    public function isDenied(): bool
    {
        return ! in_array($this, [self::Allowed, self::Already], true);
    }

    /**
     * @return list<string>
     */
    public static function deniedValues(): array
    {
        return array_values(array_map(fn (self $r): string => $r->value, array_filter(self::cases(), fn (self $r): bool => $r->isDenied())));
    }
}
