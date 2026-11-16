<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum PublishStatus: string
{
    use HasLabels;

    case Draft = 'draft';
    case Published = 'published';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }
}
