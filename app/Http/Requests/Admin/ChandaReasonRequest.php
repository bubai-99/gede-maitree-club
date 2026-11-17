<?php

namespace App\Http\Requests\Admin;

use App\Models\ChandaEntry;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelling a chanda receipt always needs a reason (it goes in the audit log and the khata).
 */
class ChandaReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $receipt = $this->route('receipt');

        return $receipt instanceof ChandaEntry && ($this->user()?->can('cancel', $receipt) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ];
    }
}
