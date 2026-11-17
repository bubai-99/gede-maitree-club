<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMode;
use App\Http\Requests\Concerns\ValidatesUtr;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Console → Collect payment: family, how many months, cash or UPI (with the UTR).
 */
class CollectPaymentRequest extends FormRequest
{
    use ValidatesUtr;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['utr' => self::cleanUtr($this->input('utr'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'family_id' => ['required', 'integer', Rule::exists('families', 'id')],
            'months' => ['required', 'integer', 'min:1', 'max:24'],
            'mode' => ['required', Rule::in([PaymentMode::Cash->value, PaymentMode::Upi->value])],
            'utr' => [...$this->utrRules(required: false), 'required_if:mode,upi'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->utrMessages();
    }
}
