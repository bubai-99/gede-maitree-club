<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A website setting saved from the console (contact details, menu, SEO text).
 * Its key is a string, so changes are written to the audit log by the console controller.
 */
#[Fillable(['key', 'value'])]
class SiteSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
