<?php

namespace App\Http\Requests\Admin;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload a bank statement (CSV) to match UPI payments.
 */
class BankStatementRequest extends FormRequest
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
            'statement' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
