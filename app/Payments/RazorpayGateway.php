<?php

namespace App\Payments;

use App\Enums\Gateway;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Razorpay Standard Checkout, server side, as the Razorpay docs describe it:
 *
 * - Orders API: POST /v1/orders (amount in paise, currency INR, receipt up to 40 characters,
 *   up to 15 notes), HTTP Basic auth with key_id:key_secret.
 * - Payment signature: hex HMAC-SHA256 of "<order_id>|<razorpay_payment_id>" with the key
 *   secret, using the order ID we stored (never the one the browser sends back).
 * - Webhook signature: hex HMAC-SHA256 of the raw request body with the webhook secret
 *   (X-Razorpay-Signature header).
 * - Payments API: GET /v1/payments/{id} to check that a payment is captured and to read its
 *   method, fee and tax (paise; the fee includes the GST shown as tax).
 *
 * Laravel's HTTP client is used instead of the razorpay/razorpay SDK so that tests can fake
 * Razorpay with Http::fake(); the endpoints and the signature checks are the same.
 */
class RazorpayGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $keyId,
        private readonly string $keySecret,
        private readonly ?string $webhookSecret = null,
        private readonly string $apiUrl = 'https://api.razorpay.com/v1',
    ) {}

    public static function configured(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    public function code(): Gateway
    {
        return Gateway::Razorpay;
    }

    /**
     * The public key ID (Checkout in the browser needs it).
     */
    public function keyId(): string
    {
        return $this->keyId;
    }

    public function begin(Payment $payment): void
    {
        $order = $this->createOrder($payment);

        $payment->forceFill(['gateway_order_id' => $order['id']])->save();
    }

    /**
     * @return array{id: string, amount: int, currency: string, status: string}
     */
    public function createOrder(Payment $payment): array
    {
        $order = $this->send(fn (PendingRequest $http): Response => $http->post('/orders', [
            'amount' => self::toPaise($payment->amount),
            'currency' => 'INR',
            'receipt' => (string) $payment->reference,
            'notes' => [
                'family_no' => $payment->family->family_no,
                'payment_id' => (string) $payment->id,
                'reference' => (string) $payment->reference,
            ],
        ]));

        $id = $order['id'] ?? null;
        $amount = $order['amount'] ?? null;

        if (! is_string($id) || ! is_int($amount) || $amount !== self::toPaise($payment->amount)) {
            Log::warning('Razorpay order did not match the payment', ['payment' => $payment->id]);

            throw new PaymentException('Online payment is not available right now. Please try again in a few minutes, or pay in cash at the club.');
        }

        return [
            'id' => $id,
            'amount' => $amount,
            'currency' => is_string($order['currency'] ?? null) ? $order['currency'] : 'INR',
            'status' => is_string($order['status'] ?? null) ? $order['status'] : 'created',
        ];
    }

    /**
     * The payment entity: id, amount, currency, status, order_id, method, fee, tax, ...
     *
     * @return array<string, mixed>
     */
    public function fetchPayment(string $paymentId): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('/payments/'.rawurlencode($paymentId)));
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $orderId.'|'.$paymentId, $this->keySecret), $signature);
    }

    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        if ($this->webhookSecret === null || $this->webhookSecret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $this->webhookSecret), $signature);
    }

    public static function toPaise(int $rupees): int
    {
        return $rupees * 100;
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     */
    private function send(callable $call): array
    {
        $http = Http::baseUrl($this->apiUrl)
            ->withBasicAuth($this->keyId, $this->keySecret)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);

        try {
            $json = $call($http)->throw()->json();

            if (is_array($json)) {
                /** @var array<string, mixed> $json */
                return $json;
            }

            Log::warning('Razorpay API sent an unexpected response');
        } catch (RequestException $e) {
            Log::warning('Razorpay API error', [
                'status' => $e->response->status(),
                'code' => $e->response->json('error.code'),
                'description' => $e->response->json('error.description'),
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Razorpay API unreachable', ['message' => $e->getMessage()]);
        }

        throw new PaymentException('Online payment is not available right now. Please try again in a few minutes, or pay in cash at the club.');
    }
}
