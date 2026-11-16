<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\App;

/**
 * Models that keep a Bengali copy of a field in "<field>_bn".
 */
trait Bilingual
{
    /**
     * The field in the page's language, falling back to English when the Bengali copy is empty.
     */
    public function tr(string $field): mixed
    {
        if (App::getLocale() === 'bn') {
            $bengali = $this->getAttribute($field.'_bn');

            if ($bengali !== null && $bengali !== '' && $bengali !== []) {
                return $bengali;
            }
        }

        return $this->getAttribute($field);
    }
}
