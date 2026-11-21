<?php

namespace App\Http\Requests\Admin;

use App\Models\SiteSetting;
use App\Support\SiteMenu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SiteSetting::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routes = array_column(SiteMenu::MAIN, 'route');
        $rules = [
            'contact.address' => ['required', 'string', 'max:255'],
            'contact.phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s-]+$/'],
            'contact.whatsapp' => ['required', 'digits_between:10,13'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.upi' => ['nullable', 'string', 'max:100', 'regex:/^[\w.\-]+@[\w.\-]+$/'],
            'contact.address_bn' => ['nullable', 'string', 'max:255'],
            'contact.hours' => ['nullable', 'string', 'max:100'],
            'contact.member_discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'contact.hours_bn' => ['nullable', 'string', 'max:100'],
            'contact.facebook' => ['nullable', 'url:https', 'max:255'],
            'contact.instagram' => ['nullable', 'url:https', 'max:255'],
            'contact.youtube' => ['nullable', 'url:https', 'max:255'],
            'menu' => ['nullable', 'array'],
            'menu.*.route' => ['required', 'distinct', Rule::in($routes)],
            'menu.*.show' => ['sometimes', 'boolean'],
            'menu.*.label' => ['nullable', 'string', 'max:40'],
            'menu.*.label_bn' => ['nullable', 'string', 'max:40'],
            'menu.*.sort' => ['nullable', 'integer', 'min:0', 'max:99'],
            'seo' => ['nullable', 'array'],
        ];

        foreach (array_keys((array) config('seo')) as $page) {
            foreach (['title', 'title_bn'] as $field) {
                $rules["seo.{$page}.{$field}"] = ['nullable', 'string', 'max:70'];
            }
            foreach (['description', 'description_bn'] as $field) {
                $rules["seo.{$page}.{$field}"] = ['nullable', 'string', 'max:300'];
            }
        }

        return ['contact' => ['required', 'array'], ...$rules];
    }

    /**
     * Settings rows to save: contact, menu and seo.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $contact = (array) $this->validated('contact', []);
        $social = array_filter([
            'facebook' => $contact['facebook'] ?? null,
            'instagram' => $contact['instagram'] ?? null,
            'youtube' => $contact['youtube'] ?? null,
        ]);
        unset($contact['facebook'], $contact['instagram'], $contact['youtube']);
        $contact['social'] = $social;
        if (isset($contact['member_discount_percent'])) {
            $contact['member_discount_percent'] = (int) $contact['member_discount_percent'];
        }

        $menu = [];
        foreach ((array) $this->validated('menu', []) as $item) {
            $menu[$item['route']] = [
                'show' => (bool) ($item['show'] ?? false),
                'label' => $item['label'] ?? null,
                'label_bn' => $item['label_bn'] ?? null,
                'sort' => (int) ($item['sort'] ?? 0),
            ];
        }

        $seo = [];
        foreach ((array) $this->validated('seo', []) as $page => $fields) {
            $seo[$page] = array_filter((array) $fields, fn ($v) => $v !== null && $v !== '');
        }

        return ['contact' => $contact, 'menu' => $menu, 'seo' => array_filter($seo)];
    }
}
