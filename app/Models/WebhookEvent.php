<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A webhook delivery already handled. Razorpay may send the same event more than once
 * (at-least-once delivery); its X-Razorpay-Event-Id lets us ignore repeats.
 */
#[Fillable(['provider', 'event_id', 'event', 'payload', 'processed_at'])]
class WebhookEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
