<?php

namespace App\Http\Requests\Concerns;

use App\Enums\PaymentStatus;
use Illuminate\Validation\Rule;

/**
 * A UPI reference (UTR) has exactly 12 digits and belongs to one live payment only
 * (waiting for the treasurer, or paid). The database has the same rule as a partial
 * unique index, so two people sending the same UTR at once cannot both get through.
 */
trait ValidatesUtr
{
    /**
     * Digits only: people paste "4123 5678 9012" or "UTR: 412356789012".
     */
    protected static function cleanUtr(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits === '' ? null : $digits;
    }

    /**
     * @return list<mixed>
     */
    protected function utrRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'regex:/^\d{12}$/',
            Rule::unique('payments', 'upi_ref')->where(fn ($query) => $query->whereIn('status', PaymentStatus::holdingUtr())),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function utrMessages(): array
    {
        return [
            'utr.required' => __('Enter the 12-digit UTR from your UPI app.'),
            'utr.required_if' => __('Enter the 12-digit UTR from your UPI app.'),
            'utr.regex' => __('A UTR has exactly 12 digits. Copy it from your UPI app’s receipt.'),
            'utr.unique' => __('This UTR is already on another payment. Check the number in your UPI app.'),
        ];
    }
}
