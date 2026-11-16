<?php

use App\Support\Bangla;
use Carbon\CarbonInterface;

if (! function_exists('digits')) {
    /**
     * Show digits in the page's language (Bengali digits on the Bengali site).
     */
    function digits(string|int|float|null $value): string
    {
        return Bangla::digits($value);
    }
}

if (! function_exists('rupees')) {
    /**
     * Whole rupees with Indian grouping, e.g. ₹1,200 (₹১,২০০ in Bengali).
     */
    function rupees(int $amount): string
    {
        return Bangla::rupees($amount);
    }
}

if (! function_exists('local_date')) {
    /**
     * A date in the page's language and digits.
     */
    function local_date(?CarbonInterface $date, string $format = 'j F Y'): string
    {
        return Bangla::date($date, $format);
    }
}
