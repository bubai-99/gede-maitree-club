<?php

namespace App\Http\Requests\Admin;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Which columns of the uploaded statement hold the date, the money received and the
 * description (column numbers, starting at 0).
 */
class BankColumnsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import', Payment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'integer', 'min:0', 'max:60'],
            'amount' => ['required', 'integer', 'min:0', 'max:60'],
            'reference' => ['required', 'integer', 'min:0', 'max:60'],
        ];
    }
}
