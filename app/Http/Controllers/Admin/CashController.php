<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Payments\Khata;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Console → Cash & bank: how much of each book is in cash and how much in the bank (as the
 * khata says), cash put in the bank, and UPI money still waiting to be matched.
 */
class CashController extends Controller
{
    public function __invoke(Request $request, Khata $khata): View
    {
        Gate::authorize('viewAny', LedgerEntry::class);

        $user = $request->user();
        $books = Book::query()->orderBy('id')->get()
            ->filter(fn (Book $book): bool => $user?->can('viewBook', [LedgerEntry::class, $book]) ?? false)
            ->values();

        $rows = $books->map(fn (Book $book): array => ['book' => $book, ...$khata->position($book)]);

        return view('admin.cash', [
            'rows' => $rows,
            'cash' => (int) $rows->sum('cash'),
            'bank' => (int) $rows->sum('bank'),
            'transfers' => LedgerEntry::query()
                ->with(['book', 'recorder'])
                ->whereIn('book_id', $books->modelKeys())
                ->whereIn('type', [LedgerType::Deposit->value, LedgerType::Withdrawal->value])
                ->latest('entry_date')->latest('id')
                ->limit(10)
                ->get(),
            'waiting' => $user?->can('viewAny', Payment::class)
                ? Payment::query()->where('status', PaymentStatus::AwaitingConfirmation->value)->selectRaw('COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total')->toBase()->first()
                : null,
            'canWrite' => $books->contains(fn (Book $b): bool => $user?->can('createIn', [LedgerEntry::class, $b]) ?? false),
        ]);
    }
}
