<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Life of a payment:
 * pending (checkout started) → pending_confirmation (UPI reference sent, manual UPI only)
 * → confirmed (paid, receipt issued). Online payments can also fail or expire;
 * the treasurer can reject a UPI reference or cancel a receipt.
 */
enum PaymentStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case AwaitingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for payment',
            self::AwaitingConfirmation => 'Waiting for the treasurer',
            self::Confirmed => 'Paid',
            self::Rejected => 'Rejected',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Chip colours for lists.
     */
    public function tone(): string
    {
        return self::TONES[$this->value];
    }

    /** @var array<string, string> */
    private const TONES = [
        'pending' => 'bg-marigold-wash text-turmeric',
        'pending_confirmation' => 'bg-marigold-wash text-turmeric',
        'confirmed' => 'bg-tulsi-wash text-tulsi',
        'rejected' => 'bg-sindoor-wash text-sindoor-deep',
        'failed' => 'bg-sindoor-wash text-sindoor-deep',
        'expired' => 'bg-paper-2 text-ink-2',
        'cancelled' => 'bg-paper-2 text-ink-2',
    ];

    /**
     * States from which money can still arrive and be confirmed.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Pending, self::AwaitingConfirmation, self::Failed, self::Expired];
    }

    /**
     * States in which a UPI reference (UTR) counts as used.
     *
     * @return list<string>
     */
    public static function holdingUtr(): array
    {
        return [self::AwaitingConfirmation->value, self::Confirmed->value];
    }
}
