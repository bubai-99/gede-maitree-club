<?php

namespace App\Http\Requests\Site;

use App\Models\Member;
use App\Support\FormOptions;
use Illuminate\Validation\Rule;

/**
 * Membership application: the applicant, the household joining with them, the plan and the pledge.
 */
class JoinRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'email' => ['nullable', 'email', 'max:255'],
            'dob' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'blood_group' => ['nullable', Rule::in(Member::BLOOD_GROUPS)],
            'address' => ['required', 'string', 'max:500'],
            'area' => ['required', Rule::in(FormOptions::keys(FormOptions::AREAS))],
            'aadhaar_last4' => ['nullable', 'digits:4'],
            'members' => ['nullable', 'array', 'max:8'],
            'members.*.name' => ['required_with:members.*.relation', 'nullable', 'string', 'max:120'],
            'members.*.relation' => ['nullable', 'string', 'max:50'],
            'members.*.dob' => ['nullable', 'date', 'before:today'],
            'plan' => ['required', Rule::in(FormOptions::keys(FormOptions::PLANS))],
            'agree' => ['accepted'],
        ];
    }

    public function payload(): array
    {
        $data = $this->safe()->except(['website', 'name', 'phone', 'email', 'agree']);
        $data['members'] = array_values(array_filter(
            $data['members'] ?? [],
            fn (mixed $m): bool => is_array($m) && filled($m['name'] ?? null),
        ));

        return $data;
    }
}
