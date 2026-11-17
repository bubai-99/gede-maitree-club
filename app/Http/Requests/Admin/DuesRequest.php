<?php

namespace App\Http\Requests\Admin;

use App\Models\Due;
use App\Models\Family;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Console → Dues: add a month for every family, waive months, or remind families.
 * Which of the three is decided by the route.
 */
class DuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = match (true) {
            $this->routeIs('admin.dues.generate') => 'generate',
            $this->routeIs('admin.dues.waive') => 'waive',
            default => 'remind',
        };

        return $this->user()?->can($ability, Due::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->routeIs('admin.dues.generate')) {
            return [
                'month' => ['required', 'date_format:Y-m', 'after_or_equal:2014-01', 'before_or_equal:'.now()->addYear()->format('Y-m')],
            ];
        }

        if ($this->routeIs('admin.dues.waive')) {
            $family = $this->route('family');

            return [
                'dues' => ['required', 'array', 'min:1'],
                'dues.*' => ['integer', Rule::exists('dues', 'id')->where('family_id', $family instanceof Family ? $family->id : 0)],
                'reason' => ['required', 'string', 'min:3', 'max:200'],
            ];
        }

        return [
            'families' => ['required', 'array', 'min:1', 'max:200'],
            'families.*' => ['integer', Rule::exists('families', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dues.required' => __('Choose at least one month.'),
            'families.required' => __('Choose at least one family.'),
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(string $key): array
    {
        /** @var array<int, int|string> $ids */
        $ids = (array) $this->validated($key);

        return array_values(array_map(intval(...), $ids));
    }
}
