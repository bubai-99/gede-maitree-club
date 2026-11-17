<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChandaMode;
use App\Models\Sponsor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A sponsor's money arrived: amount and how it was paid.
 */
class SponsorReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sponsor = $this->route('sponsor');

        return $sponsor instanceof Sponsor && ($this->user()?->can('manageChanda', $sponsor->event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'mode' => ['required', Rule::enum(ChandaMode::class)],
            'upi_ref' => ['nullable', 'required_if:mode,upi', 'string', 'regex:/^[A-Za-z0-9]{6,40}$/'],
        ];
    }
}
