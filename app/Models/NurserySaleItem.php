<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_id', 'plant_id', 'qty', 'rate'])]
class NurserySaleItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'rate' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NurserySale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(NurserySale::class, 'sale_id');
    }

    /**
     * @return BelongsTo<Plant, $this>
     */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
