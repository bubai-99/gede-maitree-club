<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

/**
 * Family payments. Members see and pay their own family's; the treasurer (fees.manage)
 * records cash, confirms or rejects UPI payments and cancels receipts. The Admin passes
 * every check (Gate::before).
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fees.manage');
    }

    /**
     * A member sees their own family's receipts; the treasurer and admin see all.
     */
    public function view(User $user, Payment $payment): bool
    {
        return ($user->family_id !== null && $user->family_id === $payment->family_id) || $user->can('fees.manage');
    }

    /**
     * Record money taken at the club (cash entry).
     */
    public function create(User $user): bool
    {
        return $user->can('fees.manage');
    }

    /**
     * Continue paying (checkout, UTR): only someone from the family that owes it.
     */
    public function pay(User $user, Payment $payment): bool
    {
        return $user->family_id !== null && $user->family_id === $payment->family_id;
    }

    public function confirm(User $user, Payment $payment): bool
    {
        return $user->can('fees.manage') && $payment->status === PaymentStatus::AwaitingConfirmation;
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $user->can('fees.manage') && $payment->status === PaymentStatus::AwaitingConfirmation;
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $user->can('fees.manage') && $payment->status === PaymentStatus::Confirmed;
    }

    /**
     * Match UPI payments against a bank statement.
     */
    public function import(User $user): bool
    {
        return $user->can('fees.manage');
    }
}
