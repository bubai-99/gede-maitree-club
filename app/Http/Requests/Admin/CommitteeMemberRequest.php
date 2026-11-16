<?php

namespace App\Http\Requests\Admin;

use App\Models\CommitteeMember;

class CommitteeMemberRequest extends WebsiteContentRequest
{
    protected function modelClass(): string
    {
        return CommitteeMember::class;
    }

    protected function routeKey(): string
    {
        return 'committee';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->commonRules(withPhoto: true),
            'name' => ['required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'role' => ['required', 'string', 'max:100'],
            'role_bn' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'since' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function memberData(): array
    {
        return [...$this->safe()->except(['action', 'photo_upload', 'remove_photo']), 'sort' => (int) $this->input('sort', 0)];
    }
}
