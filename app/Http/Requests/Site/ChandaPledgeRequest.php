<?php

namespace App\Http\Requests\Site;

use App\Support\FormOptions;
use Illuminate\Validation\Rule;

class ChandaPledgeRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'area' => ['required', Rule::in(FormOptions::keys(FormOptions::AREAS))],
            'amount' => ['required', 'integer', 'min:10', 'max:1000000'],
            'pay_by' => ['required', Rule::in(FormOptions::keys(FormOptions::PAY_BY))],
            'anonymous' => ['sometimes', 'boolean'],
        ];
    }

    public function payload(): array
    {
        return [
            ...$this->safe()->only(['area', 'amount', 'pay_by']),
            'anonymous' => $this->boolean('anonymous'),
        ];
    }
}
