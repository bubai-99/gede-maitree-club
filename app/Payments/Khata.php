<?php

namespace App\Payments;

use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Models\Book;
use App\Models\LedgerEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * The three books: hand-written entries, reversals and balances. Entries are never edited
 * or deleted; a reversal (same type and head, reverses_id set) counts as minus.
 */
class Khata
{
    /**
     * @param  array{type: LedgerType, head: LedgerHead, account: LedgerAccount, amount: int, entry_date: string, note: ?string}  $data
     */
    public function record(Book $book, array $data, User $by, ?UploadedFile $bill = null): LedgerEntry
    {
        return DB::transaction(function () use ($book, $data, $by, $bill): LedgerEntry {
            $entry = LedgerEntry::query()->create([
                'book_id' => $book->id,
                'entry_date' => $data['entry_date'],
                'type' => $data['type'],
                'account' => $data['type'] === LedgerType::Deposit ? LedgerAccount::Bank : ($data['type'] === LedgerType::Withdrawal ? LedgerAccount::Cash : $data['account']),
                'head' => $data['type']->isTransfer() ? LedgerHead::CashTransfer : $data['head'],
                'amount' => $data['amount'],
                'note' => $data['note'],
                'recorded_by' => $by->id,
            ]);

            if ($bill !== null) {
                $entry->addMedia($bill)->usingFileName('bill-'.$entry->id.'.'.$bill->guessExtension())->toMediaCollection('bill');
            }

            activity('khata')->performedOn($entry)->causedBy($by)
                ->withProperties(['book' => $book->code->value, 'type' => $entry->type->value, 'head' => $entry->head->value, 'amount' => $entry->amount])
                ->event('created')
                ->log("Khata entry: {$entry->type->englishLabel()} ₹{$entry->amount}, {$entry->head->englishLabel()} ({$book->code->englishLabel()})");

            return $entry;
        });
    }

    /**
     * Put a mistake right with a reversing entry dated today.
     *
     * @throws PaymentException
     */
    public function reverse(LedgerEntry $entry, ?User $by, string $note): LedgerEntry
    {
        return DB::transaction(function () use ($entry, $by, $note): LedgerEntry {
            $locked = LedgerEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if ($locked->reverses_id !== null || $locked->reversal()->exists()) {
                throw new PaymentException('This entry has already been reversed.');
            }

            $reversal = LedgerEntry::query()->create([
                'book_id' => $locked->book_id,
                'entry_date' => now()->toDateString(),
                'type' => $locked->type,
                'account' => $locked->account,
                'head' => $locked->head,
                'amount' => $locked->amount,
                'note' => mb_substr($note, 0, 255),
                'source_type' => $locked->source_type,
                'source_id' => $locked->source_id,
                'recorded_by' => $by?->id,
                'reverses_id' => $locked->id,
            ]);

            activity('khata')->performedOn($reversal)->causedBy($by)
                ->withProperties(['reverses' => $locked->id, 'amount' => $locked->amount])
                ->event('reversed')
                ->log("Khata entry #{$locked->id} reversed: {$note}");

            return $reversal;
        });
    }

    /**
     * Balance, cash in hand and bank of a book (or of all books) up to and including a date.
     *
     * @return array{income: int, expense: int, balance: int, cash: int, bank: int}
     */
    public function position(?Book $book = null, ?CarbonInterface $until = null): array
    {
        $rows = LedgerEntry::query()
            ->when($book, fn ($q) => $q->where('book_id', $book?->id))
            ->when($until, fn ($q) => $q->where('entry_date', '<=', $until?->toDateString()))
            ->groupBy('type', 'account')
            ->selectRaw('type, account, SUM('.LedgerEntry::SIGNED_AMOUNT.') AS total')
            ->toBase()
            ->get();

        $sum = fn (string $type, ?string $account = null): int => (int) $rows
            ->filter(fn ($row): bool => $row->type === $type && ($account === null || $row->account === $account))
            ->sum('total');

        $income = $sum(LedgerType::Income->value);
        $expense = $sum(LedgerType::Expense->value);
        $deposits = $sum(LedgerType::Deposit->value);
        $withdrawals = $sum(LedgerType::Withdrawal->value);

        $cash = $sum(LedgerType::Income->value, 'cash') - $sum(LedgerType::Expense->value, 'cash') - $deposits + $withdrawals;
        $bank = $sum(LedgerType::Income->value, 'bank') - $sum(LedgerType::Expense->value, 'bank') + $deposits - $withdrawals;

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'cash' => $cash,
            'bank' => $bank,
        ];
    }

    /**
     * Income and expense between two dates (inclusive).
     *
     * @return array{income: int, expense: int}
     */
    public function totals(?Book $book, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = LedgerEntry::query()
            ->when($book, fn ($q) => $q->where('book_id', $book?->id))
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('type', [LedgerType::Income->value, LedgerType::Expense->value])
            ->groupBy('type')
            ->selectRaw('type, SUM('.LedgerEntry::SIGNED_AMOUNT.') AS total')
            ->toBase()
            ->pluck('total', 'type');

        return [
            'income' => (int) ($rows[LedgerType::Income->value] ?? 0),
            'expense' => (int) ($rows[LedgerType::Expense->value] ?? 0),
        ];
    }
}
