<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReviewKind;
use App\Models\Review;
use Illuminate\Validation\Rule;

/**
 * A customer's review: what it is about, stars, their words in English and Bengali, who and when.
 */
class ReviewRequest extends WebsiteContentRequest
{
    protected function modelClass(): string
    {
        return Review::class;
    }

    protected function routeKey(): string
    {
        return 'review';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'kind' => ['required', Rule::enum(ReviewKind::class)],
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'max:600'],
            'body_bn' => ['nullable', 'string', 'max:600'],
            'who' => ['required', 'string', 'max:120'],
            'who_bn' => ['nullable', 'string', 'max:120'],
            'what' => ['nullable', 'string', 'max:160'],
            'what_bn' => ['nullable', 'string', 'max:160'],
            'reviewed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reviewData(): array
    {
        return [...$this->safe()->except(['action']), 'sort' => (int) $this->input('sort', 0)];
    }
}
