<?php

namespace App\Http\Requests\Admin;

use App\Models\PassScan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Change a scan just made: the head count of an admitted family, or let someone back in.
 */
class ScanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scan = $this->route('scan');

        return $scan instanceof PassScan && ($this->user()?->can('scan', $scan->event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'people' => ['exclude_with:let_in', 'required', 'integer', 'min:1', 'max:99'],
            'let_in' => ['sometimes', 'accepted'],
        ];
    }
}
