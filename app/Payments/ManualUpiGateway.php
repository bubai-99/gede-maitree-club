<?php

namespace App\Payments;

use App\Enums\Gateway;
use App\Models\Payment;
use App\Support\SiteSettings;

/**
 * UPI straight to the club's UPI ID: the member scans a QR code (or taps "Open UPI app"
 * on a phone), pays, and types the 12-digit UTR; the treasurer confirms it in the console
 * (by hand, or in one go from the bank statement).
 */
class ManualUpiGateway implements PaymentGateway
{
    public function __construct(private readonly ?string $upiId = null, private readonly ?string $payeeName = null) {}

    public function code(): Gateway
    {
        return Gateway::ManualUpi;
    }

    public function begin(Payment $payment): void
    {
        // Nothing to set up: the reference goes in the UPI note.
    }

    public function upiId(): string
    {
        if ($this->upiId !== null && $this->upiId !== '') {
            return $this->upiId;
        }

        $club = app(SiteSettings::class)->club();

        return is_string($club['upi'] ?? null) ? $club['upi'] : '';
    }

    public function payeeName(): string
    {
        if ($this->payeeName !== null && $this->payeeName !== '') {
            return $this->payeeName;
        }

        $club = app(SiteSettings::class)->club();

        return is_string($club['name'] ?? null) ? $club['name'] : (string) config('app.name');
    }

    /**
     * NPCI UPI deep link: upi://pay?pa=<UPI ID>&pn=<payee>&am=<amount>&cu=INR&tn=<note>.
     */
    public function uri(Payment $payment): string
    {
        return sprintf(
            'upi://pay?pa=%s&pn=%s&am=%s&cu=INR&tn=%s',
            rawurlencode($this->upiId()),
            rawurlencode($this->payeeName()),
            number_format($payment->amount, 2, '.', ''),
            rawurlencode($this->note($payment)),
        );
    }

    /**
     * Note shown in the member's UPI app and on the club's bank statement.
     */
    public function note(Payment $payment): string
    {
        return trim($payment->family->family_no.' '.$payment->reference);
    }
}
