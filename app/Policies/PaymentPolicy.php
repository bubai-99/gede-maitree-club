<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * A member sees their own family's receipts; the treasurer and admin see all.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->family_id === $payment->family_id || $user->can('fees.manage');
    }
}
