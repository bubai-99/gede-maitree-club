<?php

namespace App\Support;

use App\Enums\BookCode;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Sequential receipt numbers per series and year: MC-26-0142, CT-26-0018, NS-26-0311 for the
 * three books, and CH-26-0001 for puja chanda and sponsors (the chanda series; the money
 * itself goes into the Club book).
 *
 * The counter row is locked (SELECT ... FOR UPDATE) inside a transaction, so two
 * people taking money at the same moment can never get the same number, and a
 * number is only used up when the surrounding transaction commits.
 */
class ReceiptNumbers
{
    /** Counter key and prefix of the chanda series (receipt_counters.book_code = "chanda"). */
    public const CHANDA_SERIES = 'chanda';

    public const CHANDA_PREFIX = 'CH';

    public function next(BookCode $book, ?CarbonInterface $date = null): string
    {
        $year = (int) ($date ?? now())->format('Y');

        return self::format($book, $year, $this->allocate($book->value, $year));
    }

    /**
     * The next number in the chanda series, e.g. CH-26-0001.
     */
    public function nextChanda(?CarbonInterface $date = null): string
    {
        $year = (int) ($date ?? now())->format('Y');

        return self::formatChanda($year, $this->allocate(self::CHANDA_SERIES, $year));
    }

    public static function format(BookCode $book, int $year, int $number): string
    {
        return sprintf('%s-%02d-%04d', $book->receiptPrefix(), $year % 100, $number);
    }

    public static function formatChanda(int $year, int $number): string
    {
        return sprintf('%s-%02d-%04d', self::CHANDA_PREFIX, $year % 100, $number);
    }

    private function allocate(string $series, int $year): int
    {
        return DB::transaction(function () use ($series, $year): int {
            DB::table('receipt_counters')->insertOrIgnore([
                'book_code' => $series,
                'year' => $year,
                'last_no' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $current = DB::table('receipt_counters')
                ->where('book_code', $series)
                ->where('year', $year)
                ->lockForUpdate()
                ->value('last_no');

            $next = (int) $current + 1;

            DB::table('receipt_counters')
                ->where('book_code', $series)
                ->where('year', $year)
                ->update(['last_no' => $next, 'updated_at' => now()]);

            return $next;
        });
    }
}
