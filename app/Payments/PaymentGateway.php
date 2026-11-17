<?php

namespace App\Payments;

use App\Enums\Gateway;
use App\Models\Payment;

/**
 * How members pay in the app. RazorpayGateway when the Razorpay keys are set,
 * otherwise ManualUpiGateway (the club's own UPI ID, confirmed by the treasurer).
 */
interface PaymentGateway
{
    public function code(): Gateway;

    /**
     * Get a new pending payment ready for the member to pay (Razorpay: create the order).
     *
     * @throws PaymentException when the gateway cannot be reached
     */
    public function begin(Payment $payment): void;
}
