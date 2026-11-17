<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\Family;
use App\Passes\GateKeeper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The committee registers a family at the desk: family number and how many are coming.
 */
class RegisterFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && ($this->user()?->can('manageRegistrations', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'family_no' => ['required', 'string', 'max:20'],
            'people' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('family_no') && $this->family() === null) {
                    $validator->errors()->add('family_no', __('No family with this number'));
                }
            },
        ];
    }

    public function family(): ?Family
    {
        return GateKeeper::findFamily($this->string('family_no')->toString());
    }
}
