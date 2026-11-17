<?php

namespace App\Models;

use App\Enums\BookCode;
use App\Models\Concerns\Bilingual;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the three account books: club, catering, nursery.
 */
#[Fillable(['code', 'name', 'name_bn'])]
class Book extends Model
{
    use Bilingual;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => BookCode::class,
        ];
    }

    /**
     * The book with this code (created on first use if the seeder has not run).
     */
    public static function forCode(BookCode $code): self
    {
        return self::query()->firstOrCreate(['code' => $code->value], ['name' => $code->englishLabel()]);
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
