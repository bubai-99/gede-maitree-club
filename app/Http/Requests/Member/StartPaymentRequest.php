<?php

namespace App\Http\Requests\Member;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The months a member chose to pay. Only ids are sent; the amount is worked out on the
 * server from the family's own unpaid months.
 */
class StartPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->family_id !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $familyId = $user instanceof User ? $user->family_id : null;

        return [
            'dues' => ['required', 'array', 'min:1', 'max:60'],
            'dues.*' => ['required', 'integer', 'distinct', Rule::exists('dues', 'id')->where('family_id', $familyId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dues.required' => __('Choose at least one month.'),
            'dues.*.exists' => __('Some of the months you chose are already paid or waiting for the treasurer. Please choose again.'),
        ];
    }

    /**
     * @return list<int>
     */
    public function dueIds(): array
    {
        /** @var array<int, int|string> $ids */
        $ids = (array) $this->validated('dues');

        return array_values(array_map(intval(...), $ids));
    }
}
