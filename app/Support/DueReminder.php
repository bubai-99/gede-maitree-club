<?php

namespace App\Support;

use App\Models\Family;

/**
 * WhatsApp reminders: a wa.me link with the message filled in. Nothing is sent by the
 * app; the treasurer's own WhatsApp opens and they press send.
 */
final class DueReminder
{
    public static function message(Family $family, int $months, int $total): string
    {
        return __('Namaskar :name, your Gede Maitree Club subscription of :amount for :months months is due. Pay in the member app (:url), or to the treasurer at the club room in the evening. Thank you.', [
            'name' => (string) $family->tr('head_name'),
            'amount' => rupees($total),
            'months' => digits($months),
            'url' => route('member.pay'),
        ]);
    }

    public static function link(Family $family, string $message): ?string
    {
        $phone = $family->phone !== null ? preg_replace('/\D+/', '', $family->phone) : '';

        if ($phone === null || strlen($phone) < 10) {
            return null;
        }

        return 'https://wa.me/91'.substr($phone, -10).'?text='.rawurlencode($message);
    }

    /**
     * WhatsApp message with a receipt number after a payment.
     */
    public static function receiptMessage(string $receiptNo, int $amount): string
    {
        return __('Namaskar, :amount received by Gede Maitree Club. Receipt no. :receipt. Thank you.', [
            'amount' => rupees($amount),
            'receipt' => $receiptNo,
        ]);
    }
}
