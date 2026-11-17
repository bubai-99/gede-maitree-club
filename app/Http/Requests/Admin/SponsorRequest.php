<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChandaMode;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new sponsor: name, amount promised, what they get in return, and (optionally) the money
 * already received.
 */
class SponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && ($this->user()?->can('manageChanda', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'deliverable' => ['nullable', 'string', 'max:150'],
            'deliverable_bn' => ['nullable', 'string', 'max:150'],
            'received' => ['sometimes', 'boolean'],
            'mode' => ['nullable', Rule::enum(ChandaMode::class)],
            'upi_ref' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{6,40}$/'],
        ];
    }

    /**
     * @return array{name: string, name_bn: ?string, amount: int, deliverable: ?string, deliverable_bn: ?string}
     */
    public function sponsorData(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'name_bn' => $this->filled('name_bn') ? $this->string('name_bn')->trim()->toString() : null,
            'amount' => $this->integer('amount'),
            'deliverable' => $this->filled('deliverable') ? $this->string('deliverable')->trim()->toString() : null,
            'deliverable_bn' => $this->filled('deliverable_bn') ? $this->string('deliverable_bn')->trim()->toString() : null,
        ];
    }
}
