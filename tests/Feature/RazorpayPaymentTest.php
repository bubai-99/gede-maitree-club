<?php

namespace Tests\Feature;

use App\Enums\BookCode;
use App\Enums\DueStatus;
use App\Enums\Gateway;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Due;
use App\Models\Family;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Payments\PaymentGateway;
use App\Payments\PaymentService;
use App\Payments\RazorpayGateway;
use App\Payments\ReceiptRegister;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Razorpay Standard Checkout: order creation, the checkout signature, webhooks, idempotency.
 * Razorpay itself is faked with Http::fake(); no real keys are used.
 */
class RazorpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const KEY_SECRET = 'test_key_secret';

    private const WEBHOOK_SECRET = 'test_webhook_secret';

    private Family $family;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        config([
            'services.razorpay.key_id' => 'rzp_test_1234567890',
            'services.razorpay.key_secret' => self::KEY_SECRET,
            'services.razorpay.webhook_secret' => self::WEBHOOK_SECRET,
        ]);

        $this->family = Family::factory()->create(['family_no' => 'MC-0500']);
        foreach (['2026-08-01', '2026-09-01', '2026-10-01'] as $period) {
            Due::factory()->for($this->family)->create(['period' => $period, 'amount' => 100]);
        }
        $this->member = User::factory()->forFamily($this->family)->create(['phone' => '9830000001']);
        $this->member->assignRole(Role::Member->value);
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function fakeRazorpay(string $orderId = 'order_TEST0001', array $payment = []): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => fn (HttpRequest $request) => Http::response([
                'id' => $orderId,
                'entity' => 'order',
                'amount' => $request['amount'],
                'amount_paid' => 0,
                'amount_due' => $request['amount'],
                'currency' => 'INR',
                'receipt' => $request['receipt'],
                'status' => 'created',
                'attempts' => 0,
                'notes' => $request['notes'],
            ]),
            'api.razorpay.com/v1/payments/*' => Http::response(array_merge([
                'id' => 'pay_TEST0001',
                'entity' => 'payment',
                'amount' => 30000,
                'currency' => 'INR',
                'status' => 'captured',
                'order_id' => $orderId,
                'method' => 'upi',
                'captured' => true,
                'fee' => 708,
                'tax' => 108,
            ], $payment)),
        ]);
    }

    private function startPayment(): Payment
    {
        $this->actingAs($this->member)
            ->post('/app/pay', ['dues' => $this->family->dues()->pluck('id')->all()])
            ->assertRedirect();

        return Payment::query()->latest('id')->firstOrFail();
    }

    private function checkoutSignature(string $orderId, string $paymentId, string $secret = self::KEY_SECRET): string
    {
        return hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return TestResponse<Response>
     */
    private function webhook(string $event, array $payment, string $eventId = 'evt_1', ?string $secret = self::WEBHOOK_SECRET): TestResponse
    {
        $body = json_encode([
            'entity' => 'event',
            'account_id' => 'acc_TEST',
            'event' => $event,
            'contains' => ['payment'],
            'payload' => ['payment' => ['entity' => $payment]],
            'created_at' => time(),
        ], JSON_THROW_ON_ERROR);

        return $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, (string) $secret),
            'HTTP_X_RAZORPAY_EVENT_ID' => $eventId,
        ], $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function capturedEntity(Payment $payment, string $paymentId = 'pay_TEST0001'): array
    {
        return [
            'id' => $paymentId,
            'entity' => 'payment',
            'amount' => $payment->amount * 100,
            'currency' => 'INR',
            'status' => 'captured',
            'order_id' => $payment->gateway_order_id,
            'method' => 'card',
            'captured' => true,
            'fee' => 600,
            'tax' => 92,
            'notes' => [],
        ];
    }

    public function test_the_razorpay_gateway_is_used_when_keys_are_set(): void
    {
        $this->assertInstanceOf(RazorpayGateway::class, app(PaymentGateway::class));

        config(['services.razorpay.key_secret' => null]);
        $this->assertNotInstanceOf(RazorpayGateway::class, app(PaymentGateway::class));
    }

    public function test_the_pay_page_preselects_every_unpaid_month(): void
    {
        $this->actingAs($this->member)->get('/app/pay')
            ->assertOk()
            ->assertSee('Pay online')
            ->assertSee('August 2026')
            ->assertSee('October 2026');
    }

    public function test_starting_a_payment_creates_a_razorpay_order_in_paise(): void
    {
        $this->fakeRazorpay();

        $payment = $this->startPayment();

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(Gateway::Razorpay, $payment->gateway);
        $this->assertSame(300, $payment->amount);
        $this->assertSame('order_TEST0001', $payment->gateway_order_id);
        $this->assertSame(3, $payment->dues()->count());

        Http::assertSent(function (HttpRequest $request) use ($payment): bool {
            return $request->url() === 'https://api.razorpay.com/v1/orders'
                && $request->method() === 'POST'
                && $request['amount'] === 30000
                && $request['currency'] === 'INR'
                && $request['receipt'] === $payment->reference
                && $request['notes']['family_no'] === 'MC-0500'
                && $request['notes']['payment_id'] === (string) $payment->id
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('rzp_test_1234567890:'.self::KEY_SECRET));
        });
    }

    public function test_the_checkout_page_has_the_public_key_and_order_but_never_the_secret(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $this->actingAs($this->member)->get('/app/pay/'.$payment->id)
            ->assertOk()
            ->assertSee('rzp_test_1234567890')
            ->assertSee('order_TEST0001')
            ->assertSee('https://checkout.razorpay.com/v1/checkout.js', false)
            ->assertDontSee(self::KEY_SECRET)
            ->assertDontSee(self::WEBHOOK_SECRET);
    }

    public function test_the_amount_comes_from_the_months_and_not_from_the_browser(): void
    {
        $this->fakeRazorpay();

        $this->actingAs($this->member)->post('/app/pay', [
            'dues' => [$this->family->dues()->first()?->id],
            'amount' => 1,
        ])->assertRedirect();

        $payment = Payment::query()->latest('id')->firstOrFail();
        $this->assertSame(100, $payment->amount);
        Http::assertSent(fn (HttpRequest $request): bool => $request['amount'] === 10000);
    }

    public function test_a_member_cannot_pay_another_family_s_dues(): void
    {
        $this->fakeRazorpay();
        $other = Due::factory()->for(Family::factory())->create(['period' => '2026-10-01']);

        $this->actingAs($this->member)->post('/app/pay', ['dues' => [$other->id]])
            ->assertSessionHasErrors('dues.0');

        $this->assertSame(0, Payment::query()->count());
        Http::assertNothingSent();
    }

    public function test_a_member_cannot_open_or_complete_another_family_s_payment(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $stranger = User::factory()->forFamily(Family::factory()->create())->create();
        $stranger->assignRole(Role::Member->value);

        $this->actingAs($stranger)->get('/app/pay/'.$payment->id)->assertForbidden();
        $this->actingAs($stranger)->postJson('/app/pay/'.$payment->id.'/razorpay', [
            'razorpay_payment_id' => 'pay_TEST0001',
            'razorpay_order_id' => 'order_TEST0001',
            'razorpay_signature' => $this->checkoutSignature('order_TEST0001', 'pay_TEST0001'),
        ])->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()?->status);
    }

    public function test_a_valid_checkout_signature_marks_the_payment_paid_with_a_receipt_and_khata_lines(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $this->actingAs($this->member)->postJson('/app/pay/'.$payment->id.'/razorpay', [
            'razorpay_payment_id' => 'pay_TEST0001',
            'razorpay_order_id' => 'order_TEST0001',
            'razorpay_signature' => $this->checkoutSignature('order_TEST0001', 'pay_TEST0001'),
        ])->assertOk()->assertJson(['redirect' => route('member.pay.done', $payment)]);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame('pay_TEST0001', $payment->gateway_payment_id);
        $this->assertSame('upi', $payment->gateway_method);
        $this->assertSame(708, $payment->gateway_fee_paise);
        $this->assertMatchesRegularExpression('/^MC-\d{2}-0001$/', (string) $payment->receipt_no);
        $this->assertSame(0, $this->family->dues()->where('status', DueStatus::Due->value)->count());

        $income = LedgerEntry::query()->where('type', LedgerType::Income->value)->sole();
        $this->assertSame(300, $income->amount);
        $this->assertSame(LedgerAccount::Bank, $income->account);
        $this->assertSame(LedgerHead::Subscription, $income->head);
        $this->assertSame('club', $income->book->code->value);

        $fee = LedgerEntry::query()->where('type', LedgerType::Expense->value)->sole();
        $this->assertSame(LedgerHead::GatewayFee, $fee->head);
        $this->assertSame(7, $fee->amount);

        $this->assertTrue(Activity::query()->where('log_name', 'payments')->where('event', 'confirmed')->exists());

        $this->actingAs($this->member)->get('/app/pay/'.$payment->id.'/done')
            ->assertOk()->assertSee((string) $payment->receipt_no)->assertSee('₹300');
    }

    public function test_an_invalid_checkout_signature_is_rejected(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $this->actingAs($this->member)->postJson('/app/pay/'.$payment->id.'/razorpay', [
            'razorpay_payment_id' => 'pay_TEST0001',
            'razorpay_order_id' => 'order_TEST0001',
            'razorpay_signature' => $this->checkoutSignature('order_TEST0001', 'pay_TEST0001', 'wrong_secret'),
        ])->assertStatus(422);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertNull($payment->receipt_no);
        $this->assertSame(0, LedgerEntry::query()->count());
        Http::assertNotSent(fn (HttpRequest $request): bool => str_contains($request->url(), '/payments/'));
    }

    public function test_a_signature_for_another_order_is_rejected(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        // Correctly signed by Razorpay, but for a different (cheaper) order.
        $this->actingAs($this->member)->postJson('/app/pay/'.$payment->id.'/razorpay', [
            'razorpay_payment_id' => 'pay_OTHER',
            'razorpay_order_id' => 'order_OTHER',
            'razorpay_signature' => $this->checkoutSignature('order_OTHER', 'pay_OTHER'),
        ])->assertStatus(422);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()?->status);
    }

    public function test_a_captured_amount_that_differs_is_not_accepted(): void
    {
        $this->fakeRazorpay(payment: ['amount' => 100]);
        $payment = $this->startPayment();

        $this->actingAs($this->member)->postJson('/app/pay/'.$payment->id.'/razorpay', [
            'razorpay_payment_id' => 'pay_TEST0001',
            'razorpay_order_id' => 'order_TEST0001',
            'razorpay_signature' => $this->checkoutSignature('order_TEST0001', 'pay_TEST0001'),
        ])->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(PaymentService::NOTE_AMOUNT, $payment->note);
        $this->assertNull($payment->receipt_no);
    }

    public function test_an_authorized_payment_waits_for_the_capture_webhook(): void
    {
        $this->fakeRazorpay(payment: ['status' => 'authorized', 'captured' => false, 'fee' => null, 'tax' => null]);
        $payment = $this->startPayment();

        $this->actingAs($this->member)->postJson('/app/pay/'.$payment->id.'/razorpay', [
            'razorpay_payment_id' => 'pay_TEST0001',
            'razorpay_order_id' => 'order_TEST0001',
            'razorpay_signature' => $this->checkoutSignature('order_TEST0001', 'pay_TEST0001'),
        ])->assertOk();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()?->status);
        $this->actingAs($this->member)->get('/app/pay/'.$payment->id.'/done')->assertOk()->assertSee('Confirming your payment');

        $this->webhook('payment.captured', $this->capturedEntity($payment))->assertOk();
        $this->assertSame(PaymentStatus::Confirmed, $payment->fresh()?->status);
    }

    public function test_the_callback_twice_and_the_webhook_issue_one_receipt_only(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();
        $body = [
            'razorpay_payment_id' => 'pay_TEST0001',
            'razorpay_order_id' => 'order_TEST0001',
            'razorpay_signature' => $this->checkoutSignature('order_TEST0001', 'pay_TEST0001'),
        ];

        $this->actingAs($this->member)->postJson('/app/pay/'.$payment->id.'/razorpay', $body)->assertOk();
        $receipt = $payment->fresh()?->receipt_no;
        $this->actingAs($this->member)->postJson('/app/pay/'.$payment->id.'/razorpay', $body)->assertOk();
        $this->webhook('payment.captured', $this->capturedEntity($payment->fresh() ?? $payment), 'evt_a')->assertOk();
        $this->webhook('order.paid', $this->capturedEntity($payment->fresh() ?? $payment), 'evt_b')->assertOk();

        $this->assertSame($receipt, $payment->fresh()?->receipt_no);
        $this->assertSame(1, Payment::query()->whereNotNull('receipt_no')->count());
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::Income->value)->count());
        $this->assertSame(1, (int) DB::table('receipt_counters')->value('last_no'));
    }

    public function test_a_valid_webhook_marks_the_payment_paid(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $this->webhook('payment.captured', $this->capturedEntity($payment))->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame('card', $payment->gateway_method);
        $this->assertSame(6, LedgerEntry::query()->where('head', LedgerHead::GatewayFee->value)->value('amount'));
        $this->assertNotNull(WebhookEvent::query()->where('event_id', 'evt_1')->value('processed_at'));
    }

    public function test_a_webhook_with_a_wrong_signature_is_refused(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $this->webhook('payment.captured', $this->capturedEntity($payment), 'evt_1', 'not_the_secret')->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()?->status);
        $this->assertSame(0, WebhookEvent::query()->count());
    }

    public function test_a_webhook_needs_no_csrf_token_or_sign_in(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();
        auth()->logout();

        $this->webhook('payment.captured', $this->capturedEntity($payment))->assertOk();
    }

    public function test_a_repeated_webhook_event_is_ignored(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $this->webhook('payment.captured', $this->capturedEntity($payment), 'evt_same')->assertOk()->assertSee('OK');
        $this->webhook('payment.captured', $this->capturedEntity($payment), 'evt_same')->assertOk()->assertSee('Already processed');

        $this->assertSame(1, WebhookEvent::query()->count());
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::Income->value)->count());
    }

    public function test_a_failed_payment_is_marked_failed_and_a_later_capture_still_counts(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();

        $this->webhook('payment.failed', [
            'id' => 'pay_FAILED1', 'entity' => 'payment', 'amount' => 30000, 'currency' => 'INR', 'status' => 'failed',
            'order_id' => $payment->gateway_order_id, 'error_code' => 'BAD_REQUEST_ERROR', 'error_description' => 'Payment failed',
        ], 'evt_f')->assertOk();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()?->status);
        $this->actingAs($this->member)->get('/app/pay/'.$payment->id.'/done')->assertOk()->assertSee('Try again');

        // The member retried in the same checkout and it went through.
        $this->webhook('payment.captured', $this->capturedEntity($payment, 'pay_SECOND'), 'evt_c')->assertOk();
        $this->assertSame(PaymentStatus::Confirmed, $payment->fresh()?->status);
    }

    public function test_razorpay_being_down_shows_a_message_and_keeps_the_months_due(): void
    {
        Http::fake(['api.razorpay.com/*' => Http::response(['error' => ['code' => 'SERVER_ERROR', 'description' => 'down']], 500)]);

        $this->actingAs($this->member)
            ->post('/app/pay', ['dues' => $this->family->dues()->pluck('id')->all()])
            ->assertRedirect('/app/pay')
            ->assertSessionHas('error');

        $this->assertSame(PaymentStatus::Failed, Payment::query()->sole()->status);
        $this->assertSame(3, $this->family->dues()->where('status', DueStatus::Due->value)->count());
    }

    public function test_receipt_numbers_stay_in_order_with_no_gaps(): void
    {
        $service = app(PaymentService::class);

        $payments = collect(range(1, 5))->map(function (int $i): Payment {
            $family = Family::factory()->create();
            $due = Due::factory()->for($family)->create(['period' => '2026-10-01', 'amount' => 100]);
            $payment = Payment::query()->create(['family_id' => $family->id, 'amount' => 100, 'mode' => 'online', 'gateway' => 'razorpay', 'status' => 'pending', 'gateway_order_id' => 'order_N'.$i]);
            $payment->dues()->attach($due->id);

            return $payment;
        });

        foreach ($payments as $payment) {
            $service->confirm($payment);
            $service->confirm($payment);
            $service->applyRazorpayPayment($payment->fresh() ?? $payment, $this->capturedEntity($payment, 'pay_N'.$payment->id));
        }

        $numbers = Payment::query()->orderBy('id')->pluck('receipt_no')->map(fn ($no) => (int) substr((string) $no, -4))->all();
        $this->assertSame([1, 2, 3, 4, 5], $numbers);
        $this->assertSame([], app(ReceiptRegister::class)->check(BookCode::Club, (int) now()->format('Y'))['missing']);
    }

    public function test_unfinished_checkouts_expire(): void
    {
        $this->fakeRazorpay();
        $payment = $this->startPayment();
        $payment->forceFill(['created_at' => now()->subHour()])->save();

        $this->artisan('payments:expire')->assertSuccessful();

        $this->assertSame(PaymentStatus::Expired, $payment->fresh()?->status);
    }

    public function test_starting_again_replaces_an_unfinished_checkout(): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => fn (HttpRequest $request) => Http::response([
                'id' => 'order_P'.$request['notes']['payment_id'],
                'amount' => $request['amount'],
                'currency' => 'INR',
                'status' => 'created',
            ]),
        ]);
        $first = $this->startPayment();
        $second = $this->startPayment();

        $this->assertSame(PaymentStatus::Expired, $first->fresh()?->status);
        $this->assertSame(PaymentStatus::Pending, $second->fresh()?->status);
    }
}
