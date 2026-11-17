<?php

namespace App\Http\Requests\Member;

use App\Http\Requests\Concerns\ValidatesUtr;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The 12-digit UPI reference (UTR) a member copies from their UPI app.
 */
class UtrRequest extends FormRequest
{
    use ValidatesUtr;

    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment instanceof Payment && ($this->user()?->can('pay', $payment) ?? false);
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
            'utr' => $this->utrRules(required: true),
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
