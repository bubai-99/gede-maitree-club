<?php

namespace App\Http\Requests\Admin;

use App\Models\CateringPackage;

class CateringPackageRequest extends WebsiteContentRequest
{
    protected function modelClass(): string
    {
        return CateringPackage::class;
    }

    protected function routeKey(): string
    {
        return 'package';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'name' => ['required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash:ascii', $this->uniqueSlug('catering_packages')],
            'for' => ['nullable', 'string', 'max:200'],
            'for_bn' => ['nullable', 'string', 'max:200'],
            'rate' => ['required', 'integer', 'min:1', 'max:10000'],
            'min_guests' => ['required', 'integer', 'min:0', 'max:5000'],
            'popular' => ['sometimes', 'boolean'],
            'menu' => ['nullable', 'string', 'max:3000'],
            'menu_bn' => ['nullable', 'string', 'max:3000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * "What is included" is one item per line.
     *
     * @return array<string, mixed>
     */
    public function packageData(): array
    {
        return [
            ...$this->safe()->except(['action', 'menu', 'menu_bn']),
            'popular' => $this->boolean('popular'),
            'sort' => (int) $this->input('sort', 0),
            'menu' => $this->lines('menu'),
            'menu_bn' => $this->lines('menu_bn'),
        ];
    }
}
