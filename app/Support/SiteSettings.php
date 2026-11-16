<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\App;

/**
 * Website settings: club details, SEO text and menus. Values the committee saves in the
 * console win over the defaults in config/club.php and config/seo.php.
 */
class SiteSettings
{
    /** @var array<string, mixed>|null */
    private ?array $stored = null;

    /**
     * A saved setting (decoded JSON), or the default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->stored()[$key] ?? $default;
    }

    /**
     * Club details: name, address, phone, hours, social links.
     *
     * @return array<string, mixed>
     */
    public function club(): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = config('club');
        $saved = $this->get('contact', []);

        return array_replace($defaults, is_array($saved) ? array_filter($saved, fn ($v) => $v !== null && $v !== '') : []);
    }

    /**
     * Page title or description in the page's language.
     */
    public function seo(string $page, string $field): string
    {
        /** @var array<string, array<string, string>> $defaults */
        $defaults = config('seo');
        $saved = $this->get('seo', []);
        $row = array_replace($defaults[$page] ?? [], is_array($saved) && is_array($saved[$page] ?? null) ? $saved[$page] : []);

        $localised = App::getLocale() === 'bn' ? ($row[$field.'_bn'] ?? '') : '';

        return (string) ($localised !== '' ? $localised : ($row[$field] ?? ''));
    }

    /**
     * Header menu, ready for the layout.
     *
     * @return list<array{url: string, label: string, class: string, active: bool}>
     */
    public function mainMenu(): array
    {
        return array_map(fn (array $item): array => [
            'url' => route($item['route']),
            'label' => __($item['label']),
            'class' => $item['class'] ?? '',
            'active' => request()->routeIs($item['route'], $item['route'].'.*', str_replace('.index', '.*', $item['route'])),
        ], SiteMenu::available(SiteMenu::MAIN));
    }

    /**
     * schema.org NGO block for search engines.
     *
     * @return array<string, mixed>
     */
    public function organisationJsonLd(): array
    {
        $club = $this->club();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'NGO',
            'name' => $club['name'],
            'alternateName' => $club['name_bn'],
            'foundingDate' => (string) $club['estd'],
            'identifier' => $club['reg_no'],
            'url' => url('/'),
            'telephone' => $club['phone'],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $club['street'],
                'addressLocality' => $club['locality'],
                'postalCode' => $club['postal_code'],
                'addressCountry' => 'IN',
            ],
            'openingHours' => $club['opening_hours'],
        ];
    }

    /**
     * Forget values read during this request (after the console saves).
     */
    public function flush(): void
    {
        $this->stored = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function stored(): array
    {
        return $this->stored ??= SiteSetting::query()->get()
            ->mapWithKeys(fn (SiteSetting $setting): array => [$setting->key => $setting->value])
            ->all();
    }
}
