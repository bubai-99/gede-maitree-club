<?php

namespace App\Support;

/**
 * "Of every ₹100" shares for the website's spending ledgers.
 */
final class Shares
{
    /**
     * Largest-remainder rounding, so the whole-rupee shares add up to exactly 100.
     *
     * @param  array<int, int|float>  $amounts
     * @return array<int, int>
     */
    public static function of100(array $amounts): array
    {
        $total = array_sum($amounts);
        if ($total <= 0) {
            return array_map(fn (): int => 0, $amounts);
        }

        $raw = array_map(fn ($amount): float => $amount * 100 / $total, $amounts);
        $shares = array_map(fn (float $r): int => (int) floor($r), $raw);
        $order = array_keys($raw);
        usort($order, fn ($a, $b): int => ($raw[$b] - $shares[$b]) <=> ($raw[$a] - $shares[$a]));

        foreach (array_slice($order, 0, 100 - array_sum($shares)) as $i) {
            $shares[$i]++;
        }

        return $shares;
    }
}
