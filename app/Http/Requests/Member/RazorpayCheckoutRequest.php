<?php

namespace App\Http\Requests\Member;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * What Razorpay Checkout's handler returns after a successful payment.
 */
class RazorpayCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment && ($this->user()?->can('pay', $payment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'razorpay_payment_id' => ['required', 'string', 'max:40', 'regex:/^pay_[A-Za-z0-9]+$/'],
            'razorpay_order_id' => ['required', 'string', 'max:40', 'regex:/^order_[A-Za-z0-9]+$/'],
            'razorpay_signature' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
        ];
    }
}
