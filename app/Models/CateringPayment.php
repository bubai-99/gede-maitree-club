<?php

namespace App\Models;

use App\Enums\CateringPaymentKind;
use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Advance or final payment for a catering order.
 */
#[Fillable(['order_id', 'amount', 'kind', 'mode', 'receipt_no'])]
class CateringPayment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'kind' => CateringPaymentKind::class,
            'mode' => PaymentMode::class,
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
