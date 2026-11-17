<?php

namespace App\Http\Controllers\Member;

use App\Enums\Gateway;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\RazorpayCheckoutRequest;
use App\Http\Requests\Member\StartPaymentRequest;
use App\Http\Requests\Member\UtrRequest;
use App\Models\Payment;
use App\Models\User;
use App\Payments\ManualUpiGateway;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use App\Payments\PaymentService;
use App\Payments\RazorpayGateway;
use App\Support\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Member app → Pay dues. The family picks unpaid months (all of them to start with); the
 * server works out the amount from those months, never from the browser. Then either
 * Razorpay Checkout opens, or the club's UPI QR code is shown and the member sends the UTR.
 */
class PayController extends Controller
{
    public function index(Request $request, PaymentGateway $gateway): View
    {
        $family = HomeController::family($request);

        return view('member.pay.index', [
            'family' => $family,
            'dues' => $family->dues()->payable()->get(),
            'waiting' => $family->payments()->where('status', PaymentStatus::AwaitingConfirmation->value)->with('dues')->get(),
            'rejected' => $family->payments()->where('status', PaymentStatus::Rejected->value)
                ->where('cancelled_at', '>=', now()->subDays(14))->get(),
            'online' => $gateway->code() === Gateway::Razorpay,
        ]);
    }

    public function store(StartPaymentRequest $request, PaymentGateway $gateway, PaymentService $payments): RedirectResponse
    {
        $family = HomeController::family($request);

        try {
            $payment = $payments->start($family, $request->dueIds(), $gateway);
        } catch (PaymentException $e) {
            return redirect()->route('member.pay')->with('error', $e->getMessage());
        }

        return redirect()->route('member.pay.show', $payment)->with('open_checkout', true);
    }

    public function show(Request $request, Payment $payment): View|RedirectResponse
    {
        Gate::authorize('pay', $payment);

        if ($payment->status !== PaymentStatus::Pending) {
            return redirect()->route('member.pay.done', $payment);
        }

        $payment->load(['dues', 'family']);
        $user = $request->user();
        assert($user instanceof User);

        if ($payment->gateway === Gateway::Razorpay) {
            $razorpay = app(RazorpayGateway::class);

            return view('member.pay.razorpay', [
                'payment' => $payment,
                'checkout' => [
                    'key' => $razorpay->keyId(),
                    'amount' => RazorpayGateway::toPaise($payment->amount),
                    'currency' => 'INR',
                    'name' => __('Gede Maitree Club'),
                    'description' => __('Subscription').' · '.$payment->monthsLabel(),
                    'image' => asset('icons/member-180.png'),
                    'order_id' => $payment->gateway_order_id,
                    'prefill' => array_filter([
                        'name' => $user->name,
                        'email' => $user->email,
                        'contact' => $user->phone ? '+91'.User::normalisePhone($user->phone) : null,
                    ]),
                    'notes' => ['family_no' => $payment->family->family_no, 'reference' => (string) $payment->reference],
                    'theme' => ['color' => '#d21f1b'],
                ],
                'verifyUrl' => route('member.pay.razorpay', $payment),
                'autoOpen' => (bool) $request->session()->get('open_checkout', false),
            ]);
        }

        $upi = app(ManualUpiGateway::class);
        $uri = $upi->uri($payment);

        return view('member.pay.upi', [
            'payment' => $payment,
            'uri' => $uri,
            'qr' => QrCode::svg($uri, 220),
            'upiId' => $upi->upiId(),
            'note' => $upi->note($payment),
        ]);
    }

    /**
     * Razorpay Checkout's handler sends razorpay_payment_id, razorpay_order_id and
     * razorpay_signature. The signature is checked against the order ID we stored; then the
     * payment is read from Razorpay to make sure it is captured for the right amount.
     */
    public function razorpay(RazorpayCheckoutRequest $request, Payment $payment, RazorpayGateway $razorpay, PaymentService $payments): JsonResponse
    {
        $orderId = (string) $payment->gateway_order_id;
        $paymentId = $request->string('razorpay_payment_id')->toString();

        if ($payment->gateway !== Gateway::Razorpay
            || $request->string('razorpay_order_id')->toString() !== $orderId
            || ! $razorpay->verifyPaymentSignature($orderId, $paymentId, $request->string('razorpay_signature')->toString())) {
            activity('payments')->performedOn($payment)->causedBy($request->user())
                ->event('signature_failed')
                ->log("Razorpay signature check failed for {$payment->reference}");

            return response()->json(['message' => __('We could not verify this payment. If money left your account, show this page to the treasurer.')], 422);
        }

        if ($payment->isPaid()) {
            return response()->json(['redirect' => route('member.pay.done', $payment)]);
        }

        try {
            $payments->applyRazorpayPayment($payment, $razorpay->fetchPayment($paymentId));
        } catch (PaymentException $e) {
            // Razorpay could not be asked right now; the payment.captured webhook will finish it.
            Log::info('Razorpay payment fetch failed after checkout', ['payment' => $payment->id, 'message' => $e->getMessage()]);
        }

        return response()->json(['redirect' => route('member.pay.done', $payment)]);
    }

    public function utr(UtrRequest $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $payments->submitUtr($payment, $request->string('utr')->toString(), $user);
        } catch (PaymentException $e) {
            throw ValidationException::withMessages(['utr' => __($e->getMessage())]);
        }

        return redirect()->route('member.pay.done', $payment);
    }

    public function done(Payment $payment): View
    {
        Gate::authorize('pay', $payment);

        return view('member.pay.done', [
            'payment' => $payment->load(['dues', 'family', 'receiver']),
        ]);
    }
}
