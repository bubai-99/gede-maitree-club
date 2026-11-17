<?php

namespace App\Http\Controllers\Admin;

use App\Chanda\CashHolder;
use App\Chanda\ChandaBook;
use App\Chanda\ChandaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChandaEntryRequest;
use App\Http\Requests\Admin\ChandaReasonRequest;
use App\Models\ChandaArea;
use App\Models\ChandaEntry;
use App\Models\Event;
use App\Models\User;
use App\Support\Csv;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Console → Puja chanda: the drive at a glance (collected against target and budget, by area,
 * sponsors), writing receipts in the CH series, the register and printable receipts.
 * The Puja in-charge, the Treasurer and the Admin see everything; a volunteer sees and writes
 * only in the areas assigned to them.
 */
class ChandaController extends Controller
{
    /**
     * The drive the website shows, else the latest event that collects chanda.
     */
    public function index(SiteSettings $site): View|RedirectResponse
    {
        Gate::authorize('viewAny', ChandaEntry::class);

        $slug = $site->chanda()['event_slug'] ?? null;
        $event = (is_string($slug) ? Event::query()->collectingChanda()->where('slug', $slug)->first() : null)
            ?? Event::query()->collectingChanda()->first();

        if ($event !== null) {
            return redirect()->route('admin.chanda.show', $event);
        }

        return view('admin.chanda.none');
    }

    public function show(Request $request, Event $event, ChandaBook $book): View
    {
        Gate::authorize('viewChanda', $event);
        $user = $request->user();
        assert($user instanceof User);

        $manager = $user->can('manageChanda', $event);
        $areas = $event->chandaAreas()->with('collector')->get();

        return view('admin.chanda.show', [
            'event' => $event,
            'events' => Event::query()->collectingChanda()->get(['id', 'slug', 'title', 'title_bn', 'starts_at']),
            'manager' => $manager,
            'summary' => $book->summary($event),
            'areas' => $manager ? $areas : $areas->where('collector_id', $user->id)->values(),
            'sponsors' => $event->sponsors()->orderByDesc('amount')->get(),
            'recent' => $this->receiptsQuery($event, $user, $manager)->latest('id')->limit(10)->get(),
            'myCash' => $book->cashWithCollectors($event)->first(fn (CashHolder $row): bool => $row->collector->id === $user->id),
            'collectors' => $manager ? $this->collectors() : new Collection,
        ]);
    }

    public function receipts(Request $request, Event $event, ChandaBook $book): View
    {
        Gate::authorize('viewChanda', $event);
        $user = $request->user();
        assert($user instanceof User);
        $manager = $user->can('manageChanda', $event);

        return view('admin.chanda.receipts', [
            'event' => $event,
            'manager' => $manager,
            'receipts' => $this->receiptsQuery($event, $user, $manager)->orderByDesc('id')->paginate(50),
            'series' => $manager ? $book->series((int) now()->format('Y')) : null,
        ]);
    }

    public function export(Request $request, Event $event): StreamedResponse
    {
        Gate::authorize('manageChanda', $event);
        $user = $request->user();
        assert($user instanceof User);

        $rows = $this->receiptsQuery($event, $user, true)->orderBy('id')->lazy(200)->map(fn (ChandaEntry $e): array => [
            $e->receipt_no,
            $e->received_at?->format('Y-m-d'),
            $e->donor_name,
            $e->area->name ?? ($e->sponsor_id ? 'Sponsor' : ''),
            $e->mode->englishLabel(),
            $e->upi_ref,
            $e->collector?->name,
            $e->isCancelled() ? 'Cancelled' : 'Valid',
            $e->cancel_reason,
            $e->amount,
        ]);

        return Csv::download("chanda-{$event->slug}.csv", ['Receipt', 'Date', 'Donor', 'Area', 'Mode', 'UTR', 'Collected by', 'Status', 'Cancel reason', 'Amount (₹)'], $rows);
    }

    public function create(Request $request, Event $event): View
    {
        Gate::authorize('viewChanda', $event);
        $user = $request->user();
        assert($user instanceof User);

        $areas = $event->chandaAreas()->get()->filter(fn (ChandaArea $area): bool => $user->can('recordIn', [ChandaEntry::class, $area]))->values();
        abort_if($areas->isEmpty() && ! $user->can('recordIn', [ChandaEntry::class, null]), 403);

        return view('admin.chanda.create', [
            'event' => $event,
            'areas' => $areas,
            'area' => $request->integer('area') ?: null,
            'manager' => $user->can('manageChanda', $event),
            'collectors' => $user->can('manageChanda', $event) ? $this->collectors() : new Collection,
        ]);
    }

    public function store(ChandaEntryRequest $request, Event $event, ChandaBook $book): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $entry = $book->record($event, $request->entryData(), $user);

        return redirect()->route('admin.chanda.receipt', $entry)
            ->with('status', 'Chanda recorded in the Club book. Receipt :receipt.')
            ->with('status_replace', ['receipt' => $entry->receipt_no]);
    }

    public function receipt(ChandaEntry $receipt): View
    {
        Gate::authorize('view', $receipt);
        $receipt->load(['event', 'area', 'collector', 'canceller', 'sponsor']);

        $phone = preg_replace('/\D+/', '', (string) $receipt->donor_phone) ?? '';
        $whatsapp = strlen($phone) >= 10 && ! $receipt->isCancelled()
            ? 'https://wa.me/91'.substr($phone, -10).'?text='.rawurlencode(__('Namaskar :name. Thank you for your chanda of :amount for :event. Receipt :receipt. Gede Maitree Club', [
                'name' => $receipt->donor_name,
                'amount' => rupees($receipt->amount),
                'event' => $receipt->event->tr('title'),
                'receipt' => $receipt->receipt_no,
            ]))
            : null;

        return view('admin.chanda.receipt', ['receipt' => $receipt, 'whatsapp' => $whatsapp]);
    }

    public function cancel(ChandaReasonRequest $request, ChandaEntry $receipt, ChandaBook $book): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $book->cancel($receipt, $user, $request->string('reason')->trim()->toString());
        } catch (ChandaException $e) {
            return back()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return back()->with('status', 'Receipt cancelled. The number stays in the register and the khata has a reversing entry.');
    }

    /**
     * @return Builder<ChandaEntry>
     */
    private function receiptsQuery(Event $event, User $user, bool $manager): Builder
    {
        return ChandaEntry::query()->where('event_id', $event->id)->with(['area', 'collector'])
            ->when(! $manager, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('collected_by', $user->id)->orWhere('recorded_by', $user->id)));
    }

    /**
     * Committee people who may collect chanda.
     *
     * @return Collection<int, User>
     */
    private function collectors(): Collection
    {
        return User::query()->where('is_committee', true)->permission(['chanda.record', 'chanda.manage'])->orderBy('name')->get();
    }
}
