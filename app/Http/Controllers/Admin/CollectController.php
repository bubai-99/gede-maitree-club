<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DueStatus;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CollectPaymentRequest;
use App\Models\Family;
use App\Models\Payment;
use App\Models\User;
use App\Payments\DueService;
use App\Payments\PaymentException;
use App\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Console → Collect payment: find the family, pick the months, record. The receipt is
 * issued at once and shows in the member's app.
 */
class CollectController extends Controller
{
    public function create(Request $request, DueService $dues): View
    {
        Gate::authorize('create', Payment::class);

        $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'family' => ['nullable', 'integer'],
            'done' => ['nullable', 'integer'],
        ]);

        $query = trim($request->string('q')->toString());
        $family = $request->filled('family') ? Family::query()->find($request->integer('family')) : null;

        $matches = $query === '' ? collect() : Family::query()
            ->where(function ($q) use ($query): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $query).'%';
                $digits = User::normalisePhone($query);
                $q->where('family_no', 'ilike', $like)
                    ->orWhere('head_name', 'ilike', $like)
                    ->orWhere('head_name_bn', 'ilike', $like);

                if (strlen($digits) >= 4) {
                    $q->orWhere('phone', 'like', '%'.$digits.'%');
                }
            })
            ->withSum(['dues as due_total' => fn ($q) => $q->where('status', DueStatus::Due->value)], 'amount')
            ->orderBy('family_no')
            ->limit(8)
            ->get();

        if ($family === null && $matches->count() === 1) {
            $family = $matches->first();
        }

        $done = $request->filled('done')
            ? Payment::query()->with(['family', 'dues', 'receiver'])->whereKey($request->integer('done'))->where('status', PaymentStatus::Confirmed->value)->first()
            : null;

        $payable = $family?->dues()->payable()->get() ?? collect();
        $dueCount = $payable->count();

        return view('admin.collect', [
            'query' => $query,
            'matches' => $matches,
            'family' => $family,
            'payable' => $payable,
            'options' => collect([$dueCount, 1, 3, 6, 12])->filter()->unique()->sort()->values(),
            'dueAmounts' => $payable->pluck('amount')->values(),
            'fee' => $dues->monthlyFee(),
            'done' => $done !== null && $done->family_id === $family?->id ? $done : null,
        ]);
    }

    public function store(CollectPaymentRequest $request, PaymentService $payments): RedirectResponse
    {
        $family = Family::query()->findOrFail($request->integer('family_id'));
        $user = $request->user();
        assert($user instanceof User);

        try {
            $payment = $payments->recordAtCounter(
                $family,
                $request->integer('months'),
                PaymentMode::from($request->string('mode')->toString()),
                $request->filled('utr') ? $request->string('utr')->toString() : null,
                $user,
            );
        } catch (PaymentException $e) {
            throw ValidationException::withMessages(['utr' => __($e->getMessage())]);
        }

        return redirect()->route('admin.collect', ['family' => $family->id, 'done' => $payment->id])
            ->with('status', 'Recorded in the Club book. Receipt :receipt issued.')
            ->with('status_replace', ['receipt' => $payment->receipt_no]);
    }
}
