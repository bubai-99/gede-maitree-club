<?php

namespace App\Http\Requests\Admin;

use App\Models\ChandaArea;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A chanda area (lane), its collector, target and houses.
 */
class ChandaAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->event();

        return $event !== null && ($this->user()?->can('manageChanda', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $area = $this->route('area');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('chanda_areas', 'name')->where('event_id', $this->event()?->id)->ignore($area instanceof ChandaArea ? $area->id : null)],
            'name_bn' => ['nullable', 'string', 'max:100'],
            'collector_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_committee', true)],
            'target' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'houses' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'visited' => ['nullable', 'integer', 'min:0', 'max:10000', 'lte:houses'],
        ];
    }

    /**
     * @return array{name: string, name_bn: ?string, collector_id: ?int, target: int, houses: ?int, visited: ?int}
     */
    public function areaData(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'name_bn' => $this->filled('name_bn') ? $this->string('name_bn')->trim()->toString() : null,
            'collector_id' => $this->filled('collector_id') ? $this->integer('collector_id') : null,
            'target' => $this->integer('target'),
            'houses' => $this->filled('houses') ? $this->integer('houses') : null,
            'visited' => $this->filled('visited') ? $this->integer('visited') : null,
        ];
    }

    private function event(): ?Event
    {
        $event = $this->route('event');
        $area = $this->route('area');

        return $event instanceof Event ? $event : ($area instanceof ChandaArea ? $area->event : null);
    }
}
