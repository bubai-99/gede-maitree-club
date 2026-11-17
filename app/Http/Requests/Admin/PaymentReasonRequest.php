<?php

namespace App\Http\Requests\Admin;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Rejecting a UPI payment or cancelling a receipt always needs a reason (it goes in the
 * audit log and is shown to the member).
 */
class PaymentReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');
        $ability = $this->routeIs('admin.payments.cancel') ? 'cancel' : 'reject';

        return $payment instanceof Payment && ($this->user()?->can($ability, $payment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ];
    }
}
