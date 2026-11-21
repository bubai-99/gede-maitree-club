<?php

namespace App\Http\Requests\Admin;

use App\Models\NewsPost;
use App\Support\VideoEmbed;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A news post: title, summary and text in both languages, category, cover photo, more photos,
 * and video links (one per line; a caption may follow a "|").
 */
class NewsPostRequest extends WebsiteContentRequest
{
    /** Most photos in one post. */
    public const MAX_IMAGES = 24;

    protected function modelClass(): string
    {
        return NewsPost::class;
    }

    protected function routeKey(): string
    {
        return 'post';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->commonRules(withPhoto: true),
            'title' => ['required', 'string', 'max:200'],
            'title_bn' => ['nullable', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash:ascii', $this->uniqueSlug('news_posts')],
            'category_id' => ['nullable', 'integer', Rule::exists('news_categories', 'id')],
            'summary' => ['required', 'string', 'max:400'],
            'summary_bn' => ['nullable', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:30000'],
            'body_bn' => ['nullable', 'string', 'max:30000'],
            'videos' => ['nullable', 'string', 'max:5000'],
            'featured' => ['sometimes', 'boolean'],
            'images_upload' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images_upload.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->videoLines() as $n => $video) {
                    if (VideoEmbed::from($video['url']) === null) {
                        $validator->errors()->add('videos', __('Line :line is not a link (it should start with https://).', ['line' => $n + 1]));
                    }
                }
            },
        ];
    }

    /**
     * "https://youtu.be/… | Dhunuchi naach" lines → [{url, caption}].
     *
     * @return list<array{url: string, caption: ?string}>
     */
    public function videoLines(): array
    {
        $out = [];
        foreach (preg_split('/\R/', (string) $this->input('videos')) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$url, $caption] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);
            $out[] = ['url' => (string) $url, 'caption' => $caption !== '' ? $caption : null];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function postData(): array
    {
        return [
            ...$this->safe()->only(['title', 'title_bn', 'slug', 'category_id', 'summary', 'summary_bn', 'body', 'body_bn']),
            'videos' => $this->videoLines() ?: null,
            'featured' => $this->boolean('featured'),
        ];
    }
}
