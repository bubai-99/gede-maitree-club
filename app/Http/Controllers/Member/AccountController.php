<?php

namespace App\Http\Controllers\Member;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\CommitteeMember;
use App\Models\Due;
use App\Models\Payment;
use App\Payments\DueService;
use App\Payments\Reports;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The family's own records: dues, receipts, member cards and profile.
 */
class AccountController extends Controller
{
    /**
     * Statement: the family's subscription month by month for a financial year (April–March).
     */
    public function dues(Request $request, DueService $dueService): View
    {
        $family = HomeController::family($request);

        $request->validate(['fy' => ['nullable', 'integer', 'min:2014', 'max:'.Reports::currentYear()]]);
        $fy = $request->integer('fy', Reports::currentYear());
        [$from, $to] = Reports::range($fy);

        $dues = $family->dues()
            ->with(['payments' => fn ($q) => $q->whereIn('status', [PaymentStatus::Confirmed->value, PaymentStatus::AwaitingConfirmation->value])])
            ->whereBetween('period', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (Due $due): string => $due->period->format('Y-m'));

        $current = now()->format('Y-m');
        $fee = $dueService->monthlyFee();

        $rows = collect(range(0, 11))->map(function (int $i) use ($from, $dues, $current, $fee): array {
            $month = $from->copy()->addMonths($i);
            $due = $dues->get($month->format('Y-m'));

            if ($due === null) {
                return ['month' => $month, 'state' => $month->format('Y-m') > $current ? 'next' : 'none', 'amount' => $month->format('Y-m') > $current ? $fee : 0, 'receipt' => null];
            }

            $waiting = $due->payments->contains(fn (Payment $p): bool => $p->status === PaymentStatus::AwaitingConfirmation);
            $state = match ($due->status) {
                DueStatus::Paid => 'paid',
                DueStatus::Waived => 'waived',
                DueStatus::Due => $waiting ? 'wait' : 'due',
            };

            return [
                'month' => $month,
                'state' => $state,
                'amount' => $due->amount,
                'receipt' => $due->payments->first(fn (Payment $p): bool => $p->status === PaymentStatus::Confirmed),
            ];
        });

        $firstYear = max(2014, (int) ($family->joined_on !== null ? ($family->joined_on->month >= 4 ? $family->joined_on->year : $family->joined_on->year - 1) : Reports::currentYear()));

        return view('member.dues', [
            'family' => $family,
            'fy' => $fy,
            'years' => range(Reports::currentYear(), min($firstYear, Reports::currentYear())),
            'rows' => $rows,
            'paid' => (int) $rows->where('state', 'paid')->sum('amount'),
            'owed' => (int) $rows->whereIn('state', ['due', 'wait'])->sum('amount'),
            'upcoming' => (int) $rows->where('state', 'next')->sum('amount'),
            'outstanding' => $family->outstanding(),
            'payments' => $family->payments()->with('dues')->receipted()
                ->whereBetween('paid_at', [$from, $to])->get(),
        ]);
    }

    public function receipts(Request $request): View
    {
        $family = HomeController::family($request);

        $request->validate(['q' => ['nullable', 'string', 'max:30']]);
        $search = trim($request->string('q')->toString());

        $payments = $family->payments()->with('dues')->receipted()
            ->when($search !== '', fn ($q) => $q->where('receipt_no', 'ilike', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%'))
            ->paginate(20)
            ->withQueryString();

        return view('member.receipts.index', [
            'payments' => $payments,
            'search' => $search,
            'thisYear' => (int) $family->payments()->where('status', PaymentStatus::Confirmed->value)->whereYear('paid_at', now()->year)->sum('amount'),
        ]);
    }

    public function receipt(Payment $payment): View
    {
        Gate::authorize('view', $payment);
        abort_if($payment->receipt_no === null, 404);

        return view('member.receipts.show', [
            'payment' => $payment->load(['dues', 'family', 'receiver']),
        ]);
    }

    public function card(Request $request): View
    {
        $family = HomeController::family($request);
        $members = $family->members;

        return view('member.card', [
            'family' => $family,
            'members' => $members,
            'codes' => $members->mapWithKeys(fn ($member) => [$member->id => $member->member_no ? QrCode::svg(QrCode::memberCode($member), 220) : null]),
            // Signs the back of the card, as on the prototype
            'secretary' => CommitteeMember::query()->where('role', 'Secretary')->first(),
        ]);
    }

    public function profile(Request $request): View
    {
        $family = HomeController::family($request);

        return view('member.profile', [
            'user' => $request->user(),
            'family' => $family,
            'members' => $family->members,
        ]);
    }
}
