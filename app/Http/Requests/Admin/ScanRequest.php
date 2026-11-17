<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One scan at the gate: a QR text (pass or member card) or a typed family number, with the
 * phone's own id for the scan (uuid) and the gate's name.
 */
class ScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && ($this->user()?->can('scan', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'code' => ['nullable', 'required_without:family_no', 'string', 'max:200'],
            'family_no' => ['nullable', 'required_without:code', 'string', 'max:20'],
            'gate' => ['nullable', 'string', 'max:60'],
            'people' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array{uuid: string, code: ?string, family_no: ?string, gate: ?string, people: ?int}
     */
    public function scanData(): array
    {
        return [
            'uuid' => strtolower($this->string('uuid')->toString()),
            'code' => $this->filled('code') ? $this->string('code')->toString() : null,
            'family_no' => $this->filled('family_no') ? $this->string('family_no')->toString() : null,
            'gate' => $this->filled('gate') ? $this->string('gate')->trim()->toString() : null,
            'people' => $this->filled('people') ? $this->integer('people') : null,
        ];
    }
}
