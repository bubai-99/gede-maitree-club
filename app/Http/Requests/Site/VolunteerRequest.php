<?php

namespace App\Http\Requests\Site;

use App\Support\FormOptions;
use App\Support\SiteSettings;
use Illuminate\Validation\Rule;

class VolunteerRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'area' => ['required', Rule::in(array_keys(app(SiteSettings::class)->areas()))],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => [Rule::in(FormOptions::keys(FormOptions::VOLUNTEER_SKILLS))],
            'times' => ['nullable', 'array'],
            'times.*' => [Rule::in(FormOptions::keys(FormOptions::VOLUNTEER_TIMES))],
        ];
    }

    public function payload(): array
    {
        return $this->safe()->only(['area', 'skills', 'times']);
    }
}
