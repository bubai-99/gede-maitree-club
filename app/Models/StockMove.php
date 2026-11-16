<?php

namespace App\Models;

use App\Enums\StockMoveKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plants in or out of the nursery stock (qty is negative for sales and losses).
 */
#[Fillable(['plant_id', 'qty', 'kind', 'ref'])]
class StockMove extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'kind' => StockMoveKind::class,
        ];
    }

    /**
     * @return BelongsTo<Plant, $this>
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
