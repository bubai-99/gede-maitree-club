<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

enum BookCode: string
{
    use HasLabels;

    case Club = 'club';
    case Catering = 'catering';
    case Nursery = 'nursery';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Club => 'Club book',
            self::Catering => 'Catering book',
            self::Nursery => 'Nursery book',
        };
    }

    /**
     * Receipt number prefix: MC-26-0142, CT-26-0018, NS-26-0311.
     */
    public function receiptPrefix(): string
    {
        return self::PREFIXES[$this->value];
    }

    /** @var array<string, string> */
    private const PREFIXES = ['club' => 'MC', 'catering' => 'CT', 'nursery' => 'NS'];
}
