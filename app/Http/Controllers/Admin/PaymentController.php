<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gateway;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentReasonRequest;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentException;
use App\Payments\PaymentService;
use App\Payments\RazorpayGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Console → Online payments: Razorpay payments with their gateway ids, and UPI payments
 * waiting for the treasurer to confirm or reject.
 */
class PaymentController extends Controller
{
    public const TABS = ['waiting', 'online', 'review', 'all'];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $request->validate(['show' => ['nullable', Rule::in(self::TABS)]]);
        $show = $request->string('show', 'waiting')->toString();

        $payments = Payment::query()
            ->with(['family', 'dues', 'receiver'])
            ->whereNotNull('gateway')
            ->when($show === 'waiting', fn ($q) => $q->where('status', PaymentStatus::AwaitingConfirmation->value)->oldest('submitted_at'))
            ->when($show === 'online', fn ($q) => $q->where('gateway', Gateway::Razorpay->value)->latest())
            ->when($show === 'review', fn ($q) => $q->whereIn('note', [PaymentService::NOTE_OVERLAP, PaymentService::NOTE_AMOUNT])->latest())
            ->when($show === 'all', fn ($q) => $q->latest())
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments.index', [
            'show' => $show,
            'payments' => $payments,
            'counts' => [
                'waiting' => Payment::query()->where('status', PaymentStatus::AwaitingConfirmation->value)->count(),
                'review' => Payment::query()->whereIn('note', [PaymentService::NOTE_OVERLAP, PaymentService::NOTE_AMOUNT])->count(),
            ],
            'razorpay' => RazorpayGateway::configured(),
        ]);
    }

    public function confirm(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('confirm', $payment);
        $user = $request->user();
        assert($user instanceof User);

        try {
            $payment = $payments->confirm($payment, $user);
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Payment confirmed. Receipt :receipt issued.')->with('status_replace', ['receipt' => $payment->receipt_no]);
    }

    public function reject(PaymentReasonRequest $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $payments->reject($payment, $user, $request->string('reason')->toString());
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Payment rejected. The months are still due.');
    }

    public function cancel(PaymentReasonRequest $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $payments->cancel($payment, $user, $request->string('reason')->toString());
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Receipt cancelled. The months are due again and the khata has a reversing entry.');
    }
}
