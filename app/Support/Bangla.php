<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;

/**
 * Formatting helpers for a bilingual (English / Bengali) site: Bengali digits,
 * Indian-style rupee amounts and localised dates.
 */
final class Bangla
{
    /** @var list<string> */
    public const DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public static function isBengali(?string $locale = null): bool
    {
        return ($locale ?? App::getLocale()) === 'bn';
    }

    /**
     * Replace 0-9 with Bengali digits when the page is in Bengali.
     */
    public static function digits(string|int|float|null $value, ?string $locale = null): string
    {
        $value = (string) $value;

        if (! self::isBengali($locale)) {
            return $value;
        }

        return strtr($value, array_combine(range(0, 9), self::DIGITS));
    }

    /**
     * A whole number with Indian digit grouping (1,23,456), in the page's digits.
     */
    public static function number(int $value, ?string $locale = null): string
    {
        $formatted = Number::format($value, locale: 'en_IN');

        return self::digits($formatted === false ? (string) $value : $formatted, $locale);
    }

    /**
     * Whole rupees, e.g. ₹1,200 or ₹১,২০০.
     */
    public static function rupees(int $amount, ?string $locale = null): string
    {
        return '₹'.self::number($amount, $locale);
    }

    /**
     * A date in the page's language, e.g. "28 November 2026" or "২৮ নভেম্বর ২০২৬".
     */
    public static function date(?CarbonInterface $date, string $format = 'j F Y', ?string $locale = null): string
    {
        if ($date === null) {
            return '';
        }

        $locale ??= App::getLocale();

        return self::digits($date->copy()->locale($locale)->translatedFormat($format), $locale);
    }
}
