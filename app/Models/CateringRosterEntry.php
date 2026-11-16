<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A server on the roster for a catering order.
 */
#[Table('catering_roster')]
#[Fillable(['order_id', 'server_name', 'phone', 'is_leader', 'wage', 'confirmed'])]
class CateringRosterEntry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_leader' => 'boolean',
            'wage' => 'integer',
            'confirmed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CateringOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CateringOrder::class, 'order_id');
    }
}
