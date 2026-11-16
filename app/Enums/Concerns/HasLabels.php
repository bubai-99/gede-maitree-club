<?php

namespace App\Enums\Concerns;

/**
 * Backed enums whose cases have an English label (a translation key).
 */
trait HasLabels
{
    /**
     * English label of the case (translation key).
     */
    abstract public function englishLabel(): string;

    /**
     * Label in the page's language.
     */
    public function label(): string
    {
        return __($this->englishLabel());
    }

    /**
     * value => label, for select boxes.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[(string) $case->value] = $case->label();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => (string) $case->value, self::cases());
    }
}
