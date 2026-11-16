<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlantCategory;
use App\Enums\PlantLight;
use App\Enums\PlantWater;
use App\Models\Plant;
use Illuminate\Validation\Rule;

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
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function plantData(): array
    {
        return [
            ...$this->safe()->except(['action', 'photo_upload', 'remove_photo']),
            'is_new' => $this->boolean('is_new'),
            'active' => $this->boolean('active'),
            'sort' => (int) $this->input('sort', 0),
        ];
    }
}
