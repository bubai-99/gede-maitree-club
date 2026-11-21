<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlantCategory;
use App\Enums\PlantLight;
use App\Enums\PlantWater;
use App\Models\Plant;
use App\Support\VideoEmbed;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlantRequest extends WebsiteContentRequest
{
    protected function modelClass(): string
    {
        return Plant::class;
    }

    protected function routeKey(): string
    {
        return 'plant';
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
            'slug' => ['required', 'string', 'max:100', 'alpha_dash:ascii', $this->uniqueSlug('plants')],
            'category' => ['required', Rule::enum(PlantCategory::class)],
            'pot' => ['nullable', 'string', 'max:50'],
            'pot_bn' => ['nullable', 'string', 'max:50'],
            'price' => ['required', 'integer', 'min:0', 'max:100000'],
            'member_price' => ['required', 'integer', 'min:0', 'lte:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_new' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'light' => ['nullable', Rule::enum(PlantLight::class)],
            'water' => ['nullable', Rule::enum(PlantWater::class)],
            'about' => ['nullable', 'string', 'max:2000'],
            'about_bn' => ['nullable', 'string', 'max:2000'],
            'tip' => ['nullable', 'string', 'max:2000'],
            'tip_bn' => ['nullable', 'string', 'max:2000'],
            'details' => ['nullable', 'string', 'max:20000'],
            'details_bn' => ['nullable', 'string', 'max:20000'],
            'videos' => ['nullable', 'string', 'max:5000'],
            'images_upload' => ['nullable', 'array', 'max:12'],
            'images_upload.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (VideoEmbed::lines((string) $this->input('videos')) as $n => $video) {
                    if (VideoEmbed::from($video['url']) === null) {
                        $validator->errors()->add('videos', __('Line :line is not a link (it should start with https://).', ['line' => $n + 1]));
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function plantData(): array
    {
        return [
            ...$this->safe()->except(['action', 'photo_upload', 'remove_photo', 'images_upload', 'remove_images', 'videos']),
            'videos' => VideoEmbed::lines((string) $this->input('videos')) ?: null,
            'is_new' => $this->boolean('is_new'),
            'active' => $this->boolean('active'),
            'sort' => (int) $this->input('sort', 0),
        ];
    }
}
