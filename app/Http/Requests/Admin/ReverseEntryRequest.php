<?php

namespace App\Http\Requests\Admin;

use App\Models\LedgerEntry;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reversing a khata entry needs a note saying why.
 */
class ReverseEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('entry');

        return $entry instanceof LedgerEntry && ($this->user()?->can('reverse', $entry) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:200'],
        ];
    }
}
