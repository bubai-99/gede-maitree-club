<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Budget heads ("Head | head in Bengali | amount", one per line), the chanda target and whether
 * the website may show the totals.
 */
class ChandaBudgetRequest extends FormRequest
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
            'budget' => ['nullable', 'string', 'max:5000'],
            'chanda_target' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'chanda_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->lines() as $i => $line) {
                    $parts = array_map('trim', explode('|', $line));
                    $amount = end($parts);

                    if (count($parts) < 2 || $parts[0] === '' || ! preg_match('/^\d{1,9}$/', str_replace(['₹', ','], '', (string) $amount))) {
                        $validator->errors()->add('budget', __('Line :line: write it as “Head | Bengali | amount”.', ['line' => digits($i + 1)]));

                        return;
                    }
                }
            },
        ];
    }

    /**
     * @return array{budget: list<array{head: string, head_bn: ?string, amount: int}>, chanda_target: ?int, chanda_public: bool}
     */
    public function budgetData(): array
    {
        $budget = array_map(function (string $line): array {
            $parts = array_map('trim', explode('|', $line));
            $amount = (int) str_replace(['₹', ','], '', (string) array_pop($parts));

            return ['head' => $parts[0], 'head_bn' => ($parts[1] ?? '') !== '' ? $parts[1] : null, 'amount' => $amount];
        }, $this->lines());

        return [
            'budget' => $budget,
            'chanda_target' => $this->filled('chanda_target') ? $this->integer('chanda_target') : null,
            'chanda_public' => $this->boolean('chanda_public'),
        ];
    }

    /**
     * @return list<string>
     */
    private function lines(): array
    {
        $text = (string) $this->input('budget', '');

        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), fn (string $l): bool => $l !== ''));
    }
}
