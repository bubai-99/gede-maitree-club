<?php

namespace App\Support;

use App\Enums\BookCode;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Sequential receipt numbers per book and year: MC-26-0142, CT-26-0018, NS-26-0311.
 *
 * The counter row is locked (SELECT ... FOR UPDATE) inside a transaction, so two
 * people taking money at the same moment can never get the same number, and a
 * number is only used up when the surrounding transaction commits.
 */
class ReceiptNumbers
{
    public function next(BookCode $book, ?CarbonInterface $date = null): string
    {
        $year = (int) ($date ?? now())->format('Y');

        $number = DB::transaction(function () use ($book, $year): int {
            DB::table('receipt_counters')->insertOrIgnore([
                'book_code' => $book->value,
                'year' => $year,
                'last_no' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $current = DB::table('receipt_counters')
                ->where('book_code', $book->value)
                ->where('year', $year)
                ->lockForUpdate()
                ->value('last_no');

            $next = (int) $current + 1;

            DB::table('receipt_counters')
                ->where('book_code', $book->value)
                ->where('year', $year)
                ->update(['last_no' => $next, 'updated_at' => now()]);

            return $next;
        });

        return self::format($book, $year, $number);
    }

    public static function format(BookCode $book, int $year, int $number): string
    {
        return sprintf('%s-%02d-%04d', $book->receiptPrefix(), $year % 100, $number);
    }
}
