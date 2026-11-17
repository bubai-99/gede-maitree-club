<?php

namespace App\Payments;

use App\Enums\Gateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use SplFileObject;
use Throwable;

/**
 * A bank statement exported as CSV (first row = column names). The treasurer says which
 * column holds the date, the money received and the description; every 12-digit number in
 * the description is tried as a UPI reference (UTR) against UPI payments waiting for
 * confirmation, and the amount must be the same.
 */
final class BankStatement
{
    /** Most lines read from one file. */
    public const MAX_LINES = 5000;

    public const REASON_PAISE = 'The amount is not in whole rupees.';

    public const REASON_NO_UTR = 'No 12-digit UTR in this line.';

    public const REASON_NOT_WAITING = 'No UPI payment is waiting with this UTR.';

    public const REASON_USED = 'This UTR was already matched on an earlier line.';

    public const REASON_AMOUNT = 'The UTR matches, but the amount is different.';

    /**
     * @param  list<string>  $header
     * @param  list<list<string>>  $rows
     */
    private function __construct(public readonly array $header, public readonly array $rows) {}

    public static function fromFile(string $path): self
    {
        $file = new SplFileObject($path, 'r');
        $first = (string) $file->fgets();
        $delimiter = self::delimiter($first);
        $file->rewind();
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $file->setCsvControl($delimiter, '"', '');

        $header = null;
        $rows = [];

        foreach ($file as $line) {
            if (! is_array($line) || $line === [null]) {
                continue;
            }

            $cells = array_map(fn ($cell): string => trim((string) $cell), $line);

            if ($header === null) {
                $cells[0] = preg_replace('/^\x{FEFF}/u', '', $cells[0]) ?? $cells[0];
                $header = $cells;

                continue;
            }

            $rows[] = $cells;

            if (count($rows) >= self::MAX_LINES) {
                break;
            }
        }

        return new self($header ?? [], $rows);
    }

    /**
     * Best guess of the date, amount (money in) and description columns from their names.
     *
     * @return array{date: ?int, amount: ?int, reference: ?int}
     */
    public function guessColumns(): array
    {
        $find = function (array $words): ?int {
            foreach ($words as $word) {
                foreach ($this->header as $i => $name) {
                    if (str_contains(mb_strtolower($name), $word)) {
                        return $i;
                    }
                }
            }

            return null;
        };

        return [
            'date' => $find(['txn date', 'transaction date', 'value date', 'date']),
            'amount' => $find(['credit', 'deposit', 'cr amount', 'amount']),
            'reference' => $find(['description', 'narration', 'remarks', 'particulars', 'details', 'reference', 'ref']),
        ];
    }

    /**
     * @return array{
     *     matched: list<array{line: int, date: ?string, amount: int, text: string, utr: string, payment: Payment}>,
     *     unmatched: list<array{line: int, date: ?string, amount: ?int, text: string, reason: string}>,
     *     skipped: int
     * }
     */
    public function match(int $amountColumn, int $referenceColumn, ?int $dateColumn = null): array
    {
        $lines = [];
        $allUtrs = [];

        foreach ($this->rows as $i => $row) {
            $text = $row[$referenceColumn] ?? '';
            preg_match_all('/(?<!\d)\d{12}(?!\d)/', $text, $found);
            $utrs = array_values(array_unique($found[0]));
            $allUtrs = [...$allUtrs, ...$utrs];

            $lines[] = [
                'line' => $i + 2,
                'date' => $dateColumn !== null ? self::date($row[$dateColumn] ?? '') : null,
                'amount' => self::amount($row[$amountColumn] ?? ''),
                'text' => $text,
                'utrs' => $utrs,
            ];
        }

        $waiting = Payment::query()
            ->with('family')
            ->where('gateway', Gateway::ManualUpi->value)
            ->where('status', PaymentStatus::AwaitingConfirmation->value)
            ->whereIn('upi_ref', array_values(array_unique($allUtrs)))
            ->get()
            ->keyBy('upi_ref');

        $matched = [];
        $unmatched = [];
        $skipped = 0;
        $used = [];

        foreach ($lines as $line) {
            if ($line['amount'] === false) {
                $skipped++;

                continue;
            }

            $base = ['line' => $line['line'], 'date' => $line['date'], 'text' => $line['text']];

            if ($line['amount'] === null) {
                $unmatched[] = [...$base, 'amount' => null, 'reason' => self::REASON_PAISE];

                continue;
            }

            $hit = null;
            $reason = $line['utrs'] === [] ? self::REASON_NO_UTR : self::REASON_NOT_WAITING;

            foreach ($line['utrs'] as $utr) {
                $payment = $waiting->get($utr);

                if ($payment === null) {
                    continue;
                }

                if (isset($used[$payment->id])) {
                    $reason = self::REASON_USED;

                    continue;
                }

                if ($payment->amount !== $line['amount']) {
                    $reason = self::REASON_AMOUNT;

                    continue;
                }

                $hit = [$utr, $payment];

                break;
            }

            if ($hit === null) {
                $unmatched[] = [...$base, 'amount' => $line['amount'], 'reason' => $reason];

                continue;
            }

            $used[$hit[1]->id] = true;
            $matched[] = [...$base, 'amount' => $line['amount'], 'utr' => $hit[0], 'payment' => $hit[1]];
        }

        return ['matched' => $matched, 'unmatched' => $unmatched, 'skipped' => $skipped];
    }

    /**
     * Whole rupees received; null when it has paise; false when the cell is not money in
     * (blank, zero, negative or not a number).
     */
    public static function amount(string $cell): int|false|null
    {
        $clean = str_replace([',', '₹', 'INR', 'Rs.', 'Rs', 'CR', 'Cr', ' '], '', $cell);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            return false;
        }

        $value = (float) $clean;

        if ($value <= 0) {
            return false;
        }

        return fmod($value, 1.0) === 0.0 ? (int) $value : null;
    }

    public static function date(string $cell): ?string
    {
        if ($cell === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'd M Y', 'd-M-Y', 'd-M-y', 'd M y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $cell);

                if ($date !== null && $date->format($format) === $cell) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                // try the next format
            }
        }

        return null;
    }

    private static function delimiter(string $line): string
    {
        $counts = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
