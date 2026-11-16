<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Base for website CMS forms: only people allowed to edit the website (policy),
 * the draft/publish button and an optional photo upload.
 */
abstract class WebsiteContentRequest extends FormRequest
{
    /**
     * The model class edited by this form.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * The route parameter holding the record when editing.
     */
    abstract protected function routeKey(): string;

    public function authorize(): bool
    {
        $record = $this->route($this->routeKey());

        return $record instanceof Model
            ? $this->user()?->can('update', $record) ?? false
            : $this->user()?->can('create', $this->modelClass()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function commonRules(bool $withPhoto = false): array
    {
        $rules = [
            'action' => ['required', Rule::in(['draft', 'publish'])],
        ];

        if ($withPhoto) {
            $rules['photo_upload'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
            $rules['remove_photo'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * Ignore the record being edited when checking that a slug is unique.
     */
    protected function uniqueSlug(string $table): Unique
    {
        $record = $this->route($this->routeKey());

        return Rule::unique($table, 'slug')->ignore($record instanceof Model ? $record->getKey() : null);
    }

    /**
     * Split a textarea into trimmed, non-empty lines.
     *
     * @return list<string>
     */
    protected function lines(string $field): array
    {
        $text = (string) $this->input($field, '');

        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), fn (string $l): bool => $l !== ''));
    }
}
