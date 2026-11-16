<?php

namespace App\Http\Requests\Site;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for the website's public forms: a honeypot field that people never see
 * (bots fill it in), a name and an Indian mobile number.
 */
abstract class PublicFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules shared by every public form.
     *
     * @return array<string, mixed>
     */
    protected function baseRules(): array
    {
        return [
            'website' => ['prohibited'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^(\+?91[\s-]?)?[6-9]\d{4}[\s-]?\d{5}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Enter a 10-digit Indian mobile number.'),
            'website.prohibited' => __('Something went wrong. Please try again.'),
        ];
    }

    public function phone(): string
    {
        return User::normalisePhone($this->string('phone')->toString());
    }

    /**
     * What is stored with the enquiry besides name and phone.
     *
     * @return array<string, mixed>
     */
    abstract public function payload(): array;
}
