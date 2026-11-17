<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Something the person asked for cannot be done. The message is an English translation key
 * (with :placeholders filled from $replace) that can be shown to them as it is.
 */
abstract class FriendlyException extends RuntimeException
{
    /**
     * @param  array<string, string|int>  $replace
     */
    public function __construct(string $message, public readonly array $replace = [])
    {
        parent::__construct($message);
    }

    /**
     * The message in the page's language.
     */
    public function translated(): string
    {
        return __($this->getMessage(), array_map(fn (string|int $value): string => is_int($value) ? digits($value) : $value, $this->replace));
    }
}
