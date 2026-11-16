<?php

namespace App\Http\Requests\Admin;

use App\Enums\NoticeAudience;
use App\Models\Notice;
use Illuminate\Validation\Rule;

class NoticeRequest extends WebsiteContentRequest
{
    protected function modelClass(): string
    {
        return Notice::class;
    }

    protected function routeKey(): string
    {
        return 'notice';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'title' => ['required', 'string', 'max:200'],
            'title_bn' => ['nullable', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash:ascii', $this->uniqueSlug('notices')],
            'tag' => ['nullable', 'string', 'max:50'],
            'tag_bn' => ['nullable', 'string', 'max:50'],
            'summary' => ['required', 'string', 'max:1000'],
            'summary_bn' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string', 'max:10000'],
            'body_bn' => ['nullable', 'string', 'max:10000'],
            'audience' => ['required', Rule::enum(NoticeAudience::class)],
            'pinned' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function noticeData(): array
    {
        return [...$this->safe()->except(['action']), 'pinned' => $this->boolean('pinned')];
    }
}
