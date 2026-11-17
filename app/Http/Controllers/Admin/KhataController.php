<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LedgerEntryRequest;
use App\Http\Requests\Admin\ReverseEntryRequest;
use App\Models\Book;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Payments\Khata;
use App\Payments\PaymentException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Console → Khata: the three books month by month, with a running balance, monthly
 * totals, cash in hand and bank, hand-written entries (with a bill photo) and reversals.
 */
class KhataController extends Controller
{
    public function index(Request $request, Khata $khata): View
    {
        Gate::authorize('viewAny', LedgerEntry::class);

        $books = $this->visibleBooks($request);
        $seeAll = $books->count() === Book::query()->count();

        $request->validate([
            'book' => ['nullable', Rule::in([...$books->map(fn (Book $b): string => $b->code->value)->all(), ...($seeAll ? ['all'] : [])])],
            'month' => ['nullable', 'date_format:Y-m'],
            'type' => ['nullable', Rule::enum(LedgerType::class)],
            'head' => ['nullable', Rule::enum(LedgerHead::class)],
        ]);

        $code = $request->string('book', $seeAll ? 'all' : (string) $books->first()?->code->value)->toString();
        $book = $code === 'all' ? null : $books->first(fn (Book $b): bool => $b->code->value === $code);
        abort_if($book === null && ! $seeAll, 403);

        $month = $request->filled('month') ? Carbon::createFromFormat('!Y-m', $request->string('month')->toString()) : null;
        $start = ($month ?? now())->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $type = $request->enum('type', LedgerType::class);
        $head = $request->enum('head', LedgerHead::class);

        $entries = LedgerEntry::query()
            ->with(['book', 'recorder', 'reversal', 'reverses', 'media'])
            ->whereIn('book_id', $book !== null ? [$book->id] : $books->modelKeys())
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->when($type, fn ($q) => $q->where('type', $type?->value))
            ->when($head, fn ($q) => $q->where('head', $head?->value))
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $opening = $khata->position($book, $start->copy()->subDay())['balance'];
        $running = null;

        if ($type === null && $head === null) {
            $balance = $opening;
            $running = $entries->mapWithKeys(function (LedgerEntry $entry) use (&$balance): array {
                $balance += $entry->balanceEffect();

                return [$entry->id => $balance];
            });
        }

        return view('admin.khata.index', [
            'books' => $books,
            'book' => $book,
            'seeAll' => $seeAll,
            'month' => $start,
            'months' => collect(range(0, 23))->map(fn (int $i): Carbon => now()->startOfMonth()->subMonths($i)),
            'type' => $type,
            'head' => $head,
            'entries' => $entries,
            'opening' => $opening,
            'running' => $running,
            'totals' => $khata->totals($book, $start, $end),
            'position' => $khata->position($book),
            'canWrite' => $books->contains(fn (Book $b): bool => $request->user()?->can('createIn', [LedgerEntry::class, $b]) ?? false),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('viewAny', LedgerEntry::class);

        $books = $this->visibleBooks($request)->filter(fn (Book $b): bool => $request->user()?->can('createIn', [LedgerEntry::class, $b]) ?? false)->values();
        abort_if($books->isEmpty(), 403);

        $request->validate([
            'type' => ['nullable', Rule::enum(LedgerType::class)],
            'book' => ['nullable', 'string'],
        ]);

        return view('admin.khata.create', [
            'books' => $books,
            'type' => $request->enum('type', LedgerType::class) ?? LedgerType::Expense,
            'bookCode' => $request->string('book', $books->firstOrFail()->code->value)->toString(),
            'heads' => collect(LedgerType::cases())->mapWithKeys(fn (LedgerType $t): array => [
                $t->value => collect(LedgerHead::forType($t))->map(fn (LedgerHead $h): array => ['value' => $h->value, 'label' => $h->label()])->all(),
            ]),
            // Events whose spending is tracked against a budget (recent and coming pujas).
            'events' => Event::query()->where(fn ($q) => $q->where('chanda_box', true)->orWhereNotNull('budget'))
                ->where('starts_at', '>=', now()->subYear())->orderByDesc('starts_at')->get(['id', 'title', 'title_bn', 'starts_at']),
        ]);
    }

    public function store(LedgerEntryRequest $request, Khata $khata): RedirectResponse
    {
        $book = $request->book();
        $user = $request->user();
        assert($book !== null && $user instanceof User);

        $bill = $request->file('bill');
        $entry = $khata->record($book, $request->entryData(), $user, $bill instanceof UploadedFile ? $bill : null);

        return redirect()->route('admin.khata.index', ['book' => $book->code->value, 'month' => $entry->entry_date->format('Y-m')])
            ->with('status', 'Entry saved in the :book.')
            ->with('status_replace', ['book' => $book->code->label()]);
    }

    public function reverse(ReverseEntryRequest $request, LedgerEntry $entry, Khata $khata): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $khata->reverse($entry, $user, $request->string('note')->toString());
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Entry reversed. Nothing is deleted: the reversal is in the khata with your note.');
    }

    /**
     * The bill photo, from the private disk.
     */
    public function bill(Request $request, LedgerEntry $entry): Response
    {
        Gate::authorize('view', $entry);

        $media = $entry->getFirstMedia('bill');
        abort_if($media === null, 404);

        return $media->toInlineResponse($request);
    }

    /**
     * @return Collection<int, Book>
     */
    private function visibleBooks(Request $request): Collection
    {
        $user = $request->user();

        return Book::query()->orderBy('id')->get()
            ->filter(fn (Book $book): bool => $user?->can('viewBook', [LedgerEntry::class, $book]) ?? false)
            ->values();
    }
}
