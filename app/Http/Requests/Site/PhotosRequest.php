<?php

namespace App\Http\Requests\Site;

/**
 * Someone has photos or videos for the gallery: a shared-album link and a note.
 * The committee collects the files (nothing is uploaded through the website).
 */
class PhotosRequest extends PublicFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'event' => ['required', 'string', 'max:200'],
            'link' => ['nullable', 'url:https', 'max:500'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function payload(): array
    {
        return $this->safe()->only(['event', 'link', 'message']);
    }
}
