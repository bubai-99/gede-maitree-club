<?php

namespace App\Models;

use App\Enums\StockMoveKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Plants in or out of the nursery stock (qty is negative for sales and losses).
 *
 * @property int $id
 * @property int $plant_id
 * @property int $qty
 * @property StockMoveKind $kind
 * @property ?int $cost
 * @property ?int $sale_id
 * @property ?string $ref
 * @property ?int $recorded_by
 * @property ?Carbon $created_at
 */
#[Fillable(['plant_id', 'qty', 'kind', 'cost', 'sale_id', 'ref', 'recorded_by'])]
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
            'cost' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Plant, $this>
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
