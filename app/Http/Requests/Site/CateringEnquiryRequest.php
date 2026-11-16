<?php

namespace App\Http\Requests\Site;

use App\Models\CateringPackage;
use App\Support\FormOptions;
use Illuminate\Validation\Rule;

class CateringEnquiryRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'occasion' => ['required', Rule::in(FormOptions::keys(FormOptions::OCCASIONS))],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'guests' => ['required', 'integer', 'min:10', 'max:1000'],
            'package' => ['required', Rule::exists(CateringPackage::class, 'slug')->where('status', 'published')],
            'is_member' => ['sometimes', 'boolean'],
            'venue' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function payload(): array
    {
        return [
            ...$this->safe()->only(['occasion', 'event_date', 'guests', 'package', 'venue']),
            'is_member' => $this->boolean('is_member'),
        ];
    }
}
