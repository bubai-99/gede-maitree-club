<?php

namespace App\Payments;

use App\Enums\BookCode;
use App\Models\Payment;
use App\Support\ReceiptNumbers;
use Illuminate\Support\Facades\DB;

/**
 * Series check for the receipts register: every number from 1 to the counter's last number
 * must be in the register (cancelled receipts stay in it). Numbers are only used when the
 * payment is saved, so a gap means a receipt went missing and must be explained.
 */
final class ReceiptRegister
{
    /**
     * @return array{book: BookCode, year: int, last: int, first: ?string, latest: ?string, missing: list<string>}
     */
    public function check(BookCode $book, int $year): array
    {
        $last = (int) DB::table('receipt_counters')->where('book_code', $book->value)->where('year', $year)->value('last_no');
        $prefix = sprintf('%s-%02d-', $book->receiptPrefix(), $year % 100);

        $present = Payment::query()
            ->where('receipt_no', 'like', $prefix.'%')
            ->pluck('receipt_no')
            ->map(fn (?string $no): int => (int) substr((string) $no, strlen($prefix)))
            ->flip();

        $missing = [];

        for ($n = 1; $n <= $last; $n++) {
            if (! $present->has($n)) {
                $missing[] = ReceiptNumbers::format($book, $year, $n);
            }
        }

        return [
            'book' => $book,
            'year' => $year,
            'last' => $last,
            'first' => $last > 0 ? ReceiptNumbers::format($book, $year, 1) : null,
            'latest' => $last > 0 ? ReceiptNumbers::format($book, $year, $last) : null,
            'missing' => $missing,
        ];
    }
}
