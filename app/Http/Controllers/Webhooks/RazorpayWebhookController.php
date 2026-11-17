<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\Gateway;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\PaymentException;
use App\Payments\PaymentService;
use App\Payments\RazorpayGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST /webhooks/razorpay — events from Razorpay (payment.captured, order.paid,
 * payment.failed). As the Razorpay docs ask:
 *
 * - the X-Razorpay-Signature header is checked against the raw body with the webhook secret;
 * - the same event can arrive more than once, so X-Razorpay-Event-Id is stored and repeats
 *   are answered 200 without doing anything;
 * - events can arrive in any order, so each one is applied idempotently (a payment is only
 *   ever confirmed once);
 * - a 2xx answer is sent within Razorpay's 5-second window; anything else is retried.
 */
class RazorpayWebhookController extends Controller
{
    /** Events that carry a payment entity we act on. */
    private const PAYMENT_EVENTS = ['payment.captured', 'order.paid', 'payment.failed', 'payment.authorized'];

    public function __invoke(Request $request, RazorpayGateway $razorpay, PaymentService $payments): Response
    {
        $body = $request->getContent();

        if (! $razorpay->verifyWebhookSignature($body, (string) $request->header('X-Razorpay-Signature', ''))) {
            Log::warning('Razorpay webhook with a wrong signature', ['ip' => $request->ip()]);

            return response('Invalid signature', 400);
        }

        $event = json_decode($body, true);

        if (! is_array($event) || ! is_string($event['event'] ?? null)) {
            return response('Unreadable event', 400);
        }

        $name = $event['event'];
        $eventId = (string) ($request->header('X-Razorpay-Event-Id') ?: 'body-'.hash('sha256', $body));

        try {
            return DB::transaction(function () use ($event, $name, $eventId, $payments): Response {
                $isNew = DB::table('webhook_events')->insertOrIgnore([
                    'provider' => Gateway::Razorpay->value,
                    'event_id' => mb_substr($eventId, 0, 100),
                    'event' => mb_substr($name, 0, 60),
                    'payload' => json_encode($event, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]) === 1;

                if (! $isNew) {
                    return response('Already processed', 200);
                }

                $result = $this->handle($name, $event, $payments);

                DB::table('webhook_events')
                    ->where('provider', Gateway::Razorpay->value)
                    ->where('event_id', mb_substr($eventId, 0, 100))
                    ->update(['processed_at' => now()]);

                return response('OK: '.$result, 200);
            });
        } catch (Throwable $e) {
            // Nothing was stored, so Razorpay's retry will try again.
            report($e);

            return response('Could not process the event', 500);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handle(string $name, array $event, PaymentService $payments): string
    {
        if (! in_array($name, self::PAYMENT_EVENTS, true)) {
            return 'ignored';
        }

        $entity = data_get($event, 'payload.payment.entity');
        $orderId = data_get($event, 'payload.payment.entity.order_id');

        if (! is_array($entity) || ! is_string($orderId)) {
            return 'ignored';
        }

        $payment = Payment::query()
            ->where('gateway', Gateway::Razorpay->value)
            ->where('gateway_order_id', $orderId)
            ->first();

        if ($payment === null) {
            return 'unknown order';
        }

        try {
            /** @var array<string, mixed> $entity */
            return $payments->applyRazorpayPayment($payment, $entity);
        } catch (PaymentException $e) {
            // For example money for a receipt that was cancelled: keep it for the treasurer.
            Log::warning('Razorpay webhook could not be applied', ['payment' => $payment->id, 'event' => $name, 'message' => $e->getMessage()]);

            return 'needs review';
        }
    }
}
