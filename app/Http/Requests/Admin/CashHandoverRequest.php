<?php

namespace App\Http\Requests\Admin;

use App\Models\ChandaEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The treasurer receives a collector's chanda cash: the count, and a note if it differs.
 */
class CashHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('receiveCash', ChandaEntry::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'collector_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'counted' => ['required', 'integer', 'min:0', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:250'],
        ];
    }
}
