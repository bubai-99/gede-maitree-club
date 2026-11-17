<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelling an event needs a reason, shown to members (English, and Bengali if given).
 */
class CancelEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && ($this->user()?->can('cancel', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:250'],
            'reason_bn' => ['nullable', 'string', 'max:250'],
        ];
    }
}
