<?php

namespace App\Http\Requests\Site;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "The club letter" in the website footer: a WhatsApp number or an email address.
 */
class NewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'website' => ['prohibited'],
            'via' => ['required', Rule::in(['wa', 'email'])],
            'contact' => $this->input('via') === 'email'
                ? ['required', 'string', 'email', 'max:190']
                : ['required', 'string', 'regex:/^(\\+?91[\\s-]?)?[6-9]\\d{4}[\\s-]?\\d{5}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact.regex' => __('Enter a 10-digit Indian mobile number.'),
            'contact.email' => __('Enter an email address like name@example.com.'),
            'website.prohibited' => __('Something went wrong. Please try again.'),
        ];
    }

    public function byEmail(): bool
    {
        return $this->input('via') === 'email';
    }

    /**
     * The number in +91 form, or the email address in lower case.
     */
    public function contact(): string
    {
        $value = trim($this->string('contact')->toString());

        return $this->byEmail() ? mb_strtolower($value) : User::normalisePhone($value);
    }
}
