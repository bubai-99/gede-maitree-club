<?php

namespace App\Payments;

use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Models\Book;
use App\Models\LedgerEntry;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Figures for the committee and the AGM, by financial year (April to March).
 */
final class Reports
{
    public static function currentYear(): int
    {
        $now = now();

        return $now->month >= 4 ? $now->year : $now->year - 1;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function range(int $year): array
    {
        return [Carbon::create($year, 4, 1)->startOfDay(), Carbon::create($year + 1, 3, 31)->endOfDay()];
    }

    /**
     * Subscriptions collected each month of the year: receipts and rupees by mode.
     *
     * @return Collection<int, array{month: Carbon, receipts: int, cash: int, upi: int, online: int, total: int}>
     */
    public function collections(int $year): Collection
    {
        [$from, $to] = self::range($year);

        $rows = Payment::query()
            ->where('status', PaymentStatus::Confirmed->value)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw("to_char(paid_at, 'YYYY-MM') AS ym, mode, COUNT(*) AS receipts, SUM(amount) AS total")
            ->groupByRaw("to_char(paid_at, 'YYYY-MM'), mode")
            ->toBase()
            ->get();

        return collect(range(0, 11))->map(function (int $i) use ($from, $rows): array {
            $month = $from->copy()->addMonths($i);
            $ym = $month->format('Y-m');
            $sum = fn (PaymentMode $mode): int => (int) $rows->where('ym', $ym)->where('mode', $mode->value)->sum('total');

            return [
                'month' => $month,
                'receipts' => (int) $rows->where('ym', $ym)->sum('receipts'),
                'cash' => $sum(PaymentMode::Cash),
                'upi' => $sum(PaymentMode::Upi),
                'online' => $sum(PaymentMode::Online),
                'total' => (int) $rows->where('ym', $ym)->sum('total'),
            ];
        });
    }

    /**
     * Income and expenditure by head, per book, for the AGM.
     *
     * @param  list<int>  $bookIds
     * @return Collection<int, array{book: Book, income: Collection<int, array{head: LedgerHead, amount: int}>, expense: Collection<int, array{head: LedgerHead, amount: int}>, total_income: int, total_expense: int}>
     */
    public function incomeAndExpenditure(int $year, array $bookIds): Collection
    {
        [$from, $to] = self::range($year);

        $rows = LedgerEntry::query()
            ->whereIn('book_id', $bookIds)
            ->whereIn('type', [LedgerType::Income->value, LedgerType::Expense->value])
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('book_id', 'type', 'head')
            ->selectRaw('book_id, type, head, SUM('.LedgerEntry::SIGNED_AMOUNT.') AS total')
            ->toBase()
            ->get();

        return Book::query()->whereIn('id', $bookIds)->orderBy('id')->get()->map(function (Book $book) use ($rows): array {
            $side = fn (LedgerType $type): Collection => $rows
                ->where('book_id', $book->id)
                ->where('type', $type->value)
                ->map(fn ($row): array => ['head' => LedgerHead::from((string) $row->head), 'amount' => (int) $row->total])
                ->filter(fn (array $row): bool => $row['amount'] !== 0)
                ->sortByDesc('amount')
                ->values();

            $income = $side(LedgerType::Income);
            $expense = $side(LedgerType::Expense);

            return [
                'book' => $book,
                'income' => $income,
                'expense' => $expense,
                'total_income' => (int) $income->sum('amount'),
                'total_expense' => (int) $expense->sum('amount'),
            ];
        })->values()->toBase();
    }
}
