<?php

namespace App\Http\Requests\Site;

use App\Support\FormOptions;
use Illuminate\Validation\Rule;

class ContactRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'topic' => ['required', Rule::in(FormOptions::keys(FormOptions::CONTACT_TOPICS))],
            'message' => ['required', 'string', 'max:2000'],
            'reply_by' => ['required', Rule::in(FormOptions::keys(FormOptions::REPLY_BY))],
        ];
    }

    public function payload(): array
    {
        return $this->safe()->only(['topic', 'message', 'reply_by']);
    }
}
