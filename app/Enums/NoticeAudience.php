<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum NoticeAudience: string
{
    use HasLabels;

    case Public = 'public';
    case Members = 'members';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Public => 'Everyone',
            self::Members => 'Members only',
        };
    }
}
