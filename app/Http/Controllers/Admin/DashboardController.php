<?php

namespace App\Http\Controllers\Admin;

use App\Chanda\ChandaBook;
use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\Family;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Plant;
use App\Models\User;
use App\Payments\Khata;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Console → Today: this month's books, the puja chanda drive, and the list of things
 * waiting for the person signed in (each item only for the roles that can act on it).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, Khata $khata, ChandaBook $chandaBook, SiteSettings $site): View
    {
        $user = $request->user();
        assert($user instanceof User);

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        // This month's books: one row per book the person may see
        $books = $user->can('khata.open')
            ? Book::query()->orderBy('id')->get()
                ->filter(fn (Book $book): bool => $user->can('viewBook', [LedgerEntry::class, $book]))
                ->map(fn (Book $book): array => ['book' => $book] + $khata->totals($book, $start, $end))
                ->values()
            : collect();

        // The chanda drive the website shows, else the latest one collecting
        $chanda = null;
        if ($user->can('chanda.manage')) {
            $slug = $site->chanda()['event_slug'] ?? null;
            $event = (is_string($slug) ? Event::query()->collectingChanda()->where('slug', $slug)->first() : null)
                ?? Event::query()->collectingChanda()->first();
            if ($event !== null) {
                $chanda = ['event' => $event] + $chandaBook->summary($event);
            }
        }

        $fees = $user->can('fees.manage');
        $waiting = fn () => Payment::query()->where('status', PaymentStatus::AwaitingConfirmation->value);

        return view('admin.dashboard', [
            'user' => $user,
            'books' => $books,
            'chanda' => $chanda,
            'soon' => $user->can('website.view')
                ? Event::query()->published()->upcoming()->where('starts_at', '<=', now()->addDays(10))->first()
                : null,
            'upiWaiting' => $fees ? $waiting()->with(['family', 'dues'])->oldest('submitted_at')->limit(5)->get() : collect(),
            'upiWaitingCount' => $fees ? $waiting()->count() : 0,
            'openEnquiries' => Enquiry::query()->open()->count(),
            'latestEnquiries' => Enquiry::query()->open()->latest()->limit(3)->get(),
            'owing' => $fees
                ? Family::query()->whereHas('dues', fn ($q) => $q->where('status', DueStatus::Due->value), '>=', 3)->orderBy('family_no')->get()
                : collect(),
            'cashOut' => $user->can('chanda.cash') ? $chandaBook->cashWithCollectors() : collect(),
            'drafts' => $user->can('website.view') ? Event::query()->where('status', PublishStatus::Draft->value)->count() : 0,
            'lowStock' => $user->can('website.view')
                ? Plant::query()->published()->where('active', true)->where('stock', '<', 10)->orderBy('stock')->limit(5)->get()
                : collect(),
            'latest' => $books->isNotEmpty()
                ? LedgerEntry::query()->with(['book', 'recorder', 'reversal', 'media', 'source'])
                    ->whereIn('book_id', $books->map(fn (array $row): int => $row['book']->id))
                    ->orderByDesc('entry_date')->orderByDesc('id')->limit(6)->get()
                : collect(),
        ]);
    }
}
