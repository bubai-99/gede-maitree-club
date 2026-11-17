<?php

namespace App\Chanda;

use App\Enums\BookCode;
use App\Enums\ChandaMode;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\SponsorStatus;
use App\Models\Book;
use App\Models\CashHandover;
use App\Models\ChandaArea;
use App\Models\ChandaEntry;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\Sponsor;
use App\Models\User;
use App\Payments\Khata;
use App\Payments\PaymentException;
use App\Support\ReceiptNumbers;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The puja chanda book. Every gift gets the next number in the chanda series (CH-26-0001) in
 * the same transaction that saves it, so a failed save gives the number back and the series has
 * no gaps. The money is joma in the Club book (head "Puja chanda", or "Sponsorship" for a
 * sponsor), cash in hand for cash and bank for UPI, tagged with the event. Posting is
 * idempotent: the entry row is locked and remembers its khata line.
 */
class ChandaBook
{
    public function __construct(private readonly ReceiptNumbers $numbers, private readonly Khata $khata) {}

    /**
     * @param  array{donor_name: string, donor_phone?: ?string, family_id?: ?int, area?: ?ChandaArea, amount: int, mode: ChandaMode, upi_ref?: ?string, show_name?: bool, collected_by?: ?int, received_at?: ?CarbonInterface, sponsor?: ?Sponsor}  $data
     */
    public function record(Event $event, array $data, User $by): ChandaEntry
    {
        return DB::transaction(function () use ($event, $data, $by): ChandaEntry {
            $receivedAt = Carbon::instance($data['received_at'] ?? now());

            $entry = ChandaEntry::query()->create([
                'event_id' => $event->id,
                'chanda_area_id' => isset($data['area']) ? $data['area']->id : null,
                'family_id' => $data['family_id'] ?? null,
                'sponsor_id' => isset($data['sponsor']) ? $data['sponsor']->id : null,
                'donor_name' => $data['donor_name'],
                'donor_phone' => $data['donor_phone'] ?? null,
                'show_name' => $data['show_name'] ?? false,
                'amount' => $data['amount'],
                'mode' => $data['mode'],
                'upi_ref' => $data['mode'] === ChandaMode::Upi ? ($data['upi_ref'] ?? null) : null,
                'collected_by' => $data['collected_by'] ?? $by->id,
                'recorded_by' => $by->id,
                'receipt_no' => $this->numbers->nextChanda($receivedAt),
                'received_at' => $receivedAt,
            ]);

            $this->post($entry, $by);

            activity('chanda')->performedOn($entry)->causedBy($by)
                ->withProperties(['receipt' => $entry->receipt_no, 'amount' => $entry->amount, 'mode' => $entry->mode->value, 'event' => $event->slug])
                ->event('created')
                ->log("Chanda receipt {$entry->receipt_no}: ₹{$entry->amount}");

            return $entry;
        });
    }

    /**
     * The receipt's income line in the Club book. Calling it again returns the same line.
     */
    public function post(ChandaEntry $entry, ?User $by = null): LedgerEntry
    {
        return DB::transaction(function () use ($entry, $by): LedgerEntry {
            $locked = ChandaEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if ($locked->ledger_entry_id !== null) {
                return LedgerEntry::query()->findOrFail($locked->ledger_entry_id);
            }

            $line = new LedgerEntry([
                'book_id' => Book::forCode(BookCode::Club)->id,
                'event_id' => $locked->event_id,
                'entry_date' => ($locked->received_at ?? $locked->created_at ?? now())->toDateString(),
                'type' => LedgerType::Income,
                'account' => $locked->mode->account(),
                'head' => $locked->sponsor_id !== null ? LedgerHead::Sponsorship : LedgerHead::PujaChanda,
                'amount' => $locked->amount,
                'note' => mb_substr("{$locked->receipt_no} · {$locked->donor_name}", 0, 255),
                'recorded_by' => $by->id ?? $locked->recorded_by,
            ]);
            $line->source()->associate($locked);
            $line->save();

            $locked->forceFill(['ledger_entry_id' => $line->id])->save();
            $entry->setRawAttributes($locked->getAttributes(), true);

            return $line;
        });
    }

    /**
     * Cancel a receipt: the number stays in the series marked cancelled and the income is
     * reversed in the khata. A sponsor's money goes back to promised.
     *
     * @throws ChandaException
     */
    public function cancel(ChandaEntry $entry, User $by, string $reason): ChandaEntry
    {
        return DB::transaction(function () use ($entry, $by, $reason): ChandaEntry {
            $locked = ChandaEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                throw new ChandaException('This receipt is already cancelled.');
            }

            $locked->forceFill(['cancelled_at' => now(), 'cancelled_by' => $by->id, 'cancel_reason' => $reason])->save();

            $line = $locked->ledger_entry_id !== null ? LedgerEntry::query()->find($locked->ledger_entry_id) : null;

            if ($line !== null) {
                try {
                    $this->khata->reverse($line, $by, "Chanda receipt {$locked->receipt_no} cancelled: {$reason}");
                } catch (PaymentException) {
                    // Already reversed by hand in the khata: nothing more to undo.
                }
            }

            if ($locked->sponsor_id !== null) {
                Sponsor::query()->whereKey($locked->sponsor_id)->update(['status' => SponsorStatus::Promised->value, 'updated_at' => now()]);
            }

            activity('chanda')->performedOn($locked)->causedBy($by)
                ->withProperties(['receipt' => $locked->receipt_no, 'reason' => $reason])
                ->event('cancelled')
                ->log("Chanda receipt {$locked->receipt_no} cancelled: {$reason}");

            return $locked;
        });
    }

    /**
     * A sponsor's money arrived: a chanda receipt (head Sponsorship) and the sponsor marked received.
     *
     * @throws ChandaException
     */
    public function receiveSponsor(Sponsor $sponsor, int $amount, ChandaMode $mode, ?string $upiRef, User $by): ChandaEntry
    {
        return DB::transaction(function () use ($sponsor, $amount, $mode, $upiRef, $by): ChandaEntry {
            $locked = Sponsor::query()->whereKey($sponsor->id)->lockForUpdate()->firstOrFail();

            if ($locked->isReceived()) {
                throw new ChandaException('This sponsor’s money is already received.');
            }

            $event = Event::query()->findOrFail($locked->event_id);
            $entry = $this->record($event, [
                'donor_name' => $locked->name,
                'amount' => $amount,
                'mode' => $mode,
                'upi_ref' => $upiRef,
                'show_name' => true,
                'sponsor' => $locked,
            ], $by);

            $locked->forceFill(['status' => SponsorStatus::Received])->save();

            return $entry;
        });
    }

    /**
     * Cash a collector hands to the treasurer: every cash receipt they hold that has not been
     * handed over yet. A count that differs from the receipts needs a note.
     *
     * @throws ChandaException
     */
    public function handOver(User $collector, int $counted, ?string $note, User $receivedBy, ?Event $event = null): CashHandover
    {
        return DB::transaction(function () use ($collector, $counted, $note, $receivedBy, $event): CashHandover {
            $entries = ChandaEntry::query()->valid()
                ->where('collected_by', $collector->id)
                ->where('mode', ChandaMode::Cash->value)
                ->whereNull('handover_id')
                ->when($event, fn ($q) => $q->where('event_id', $event?->id))
                ->orderBy('id')->lockForUpdate()->get();

            if ($entries->isEmpty()) {
                throw new ChandaException('This collector has no chanda cash to hand over.');
            }

            $expected = (int) $entries->sum('amount');

            if ($counted !== $expected && trim((string) $note) === '') {
                throw new ChandaException('The count is different from the receipts. Write why.');
            }

            $handover = CashHandover::query()->create([
                'event_id' => $event?->id,
                'collector_id' => $collector->id,
                'received_by' => $receivedBy->id,
                'expected' => $expected,
                'counted' => $counted,
                'receipts' => $entries->count(),
                'note' => $note,
            ]);

            ChandaEntry::query()->whereIn('id', $entries->modelKeys())->update(['handover_id' => $handover->id, 'updated_at' => now()]);

            activity('chanda')->performedOn($handover)->causedBy($receivedBy)
                ->withProperties(['collector' => $collector->id, 'expected' => $expected, 'counted' => $counted])
                ->event('handover')
                ->log("Chanda cash ₹{$counted} received from {$collector->name}");

            return $handover;
        });
    }

    /**
     * Chanda cash still with each collector: amount, receipts and the oldest receipt's date.
     *
     * @return Collection<int, CashHolder>
     */
    public function cashWithCollectors(?Event $event = null): Collection
    {
        $rows = ChandaEntry::query()->valid()
            ->where('mode', ChandaMode::Cash->value)
            ->whereNull('handover_id')
            ->whereNotNull('collected_by')
            ->when($event, fn ($q) => $q->where('event_id', $event?->id))
            ->groupBy('collected_by')
            ->selectRaw('collected_by, SUM(amount) AS amount, COUNT(*) AS receipts, MIN(received_at) AS since')
            ->toBase()->get();

        $users = User::query()->whereIn('id', $rows->pluck('collected_by'))->get()->keyBy('id');
        $holders = [];

        foreach ($rows as $row) {
            $collector = $users->get($row->collected_by);

            if ($collector instanceof User) {
                $holders[] = new CashHolder($collector, (int) $row->amount, (int) $row->receipts, Carbon::parse($row->since));
            }
        }

        return collect($holders)->sortByDesc('amount')->values();
    }

    /**
     * Series check: every chanda number from 1 to the counter's last number for the year must
     * exist (cancelled receipts stay).
     *
     * @return array{year: int, last: int, latest: ?string, missing: list<string>}
     */
    public function series(int $year): array
    {
        $last = (int) DB::table('receipt_counters')->where('book_code', ReceiptNumbers::CHANDA_SERIES)->where('year', $year)->value('last_no');
        $prefix = sprintf('%s-%02d-', ReceiptNumbers::CHANDA_PREFIX, $year % 100);

        $present = ChandaEntry::query()->where('receipt_no', 'like', $prefix.'%')->pluck('receipt_no')
            ->map(fn (?string $no): int => (int) substr((string) $no, strlen($prefix)))
            ->flip();

        $missing = [];

        for ($n = 1; $n <= $last; $n++) {
            if (! $present->has($n)) {
                $missing[] = ReceiptNumbers::formatChanda($year, $n);
            }
        }

        return [
            'year' => $year,
            'last' => $last,
            'latest' => $last > 0 ? ReceiptNumbers::formatChanda($year, $last) : null,
            'missing' => $missing,
        ];
    }

    /**
     * Budget against money in and money spent for one event, in rupees.
     *
     * @return array{budget: int, target: int, door: int, sponsors: int, promised: int, collected: int, spent: int, by_area: array<int, int>}
     */
    public function summary(Event $event): array
    {
        $valid = fn () => ChandaEntry::query()->valid()->where('event_id', $event->id);
        $door = (int) $valid()->whereNull('sponsor_id')->sum('amount');
        $sponsors = (int) $valid()->whereNotNull('sponsor_id')->sum('amount');

        $spent = (int) LedgerEntry::query()
            ->where('event_id', $event->id)
            ->where('type', LedgerType::Expense->value)
            ->sum(DB::raw(LedgerEntry::SIGNED_AMOUNT));

        $byArea = $valid()->whereNotNull('chanda_area_id')->groupBy('chanda_area_id')
            ->selectRaw('chanda_area_id, SUM(amount) AS total')->toBase()->pluck('total', 'chanda_area_id')
            ->map(fn ($total): int => (int) $total)->all();

        return [
            'budget' => $event->budgetTotal(),
            'target' => (int) $event->chanda_target,
            'door' => $door,
            'sponsors' => $sponsors,
            'promised' => (int) Sponsor::query()->where('event_id', $event->id)->where('status', SponsorStatus::Promised->value)->sum('amount'),
            'collected' => $door + $sponsors,
            'spent' => $spent,
            'by_area' => $byArea,
        ];
    }
}
