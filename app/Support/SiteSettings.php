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
    /** The "Somewhere else" choice, always last in the lane list. */
    public const ELSEWHERE = 'Elsewhere';

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
        $saved = $this->menuSettings();
        $items = [];

        foreach (SiteMenu::available(SiteMenu::MAIN) as $position => $item) {
            $setting = $saved[$item['route']] ?? [];

            if (! ($setting['show'] ?? true)) {
                continue;
            }

            $items[] = [
                'url' => route($item['route']),
                'label' => local_text($setting['label'] ?? null, $setting['label_bn'] ?? null) ?: __($item['label']),
                'class' => $item['class'] ?? '',
                'active' => request()->routeIs($item['route'], str_replace('.index', '.*', $item['route'])),
                'sort' => (int) ($setting['sort'] ?? $position),
            ];
        }

        usort($items, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(fn (array $item): array => array_diff_key($item, ['sort' => true]), $items);
    }

    /**
     * Saved menu choices per route: show, label, label_bn, sort.
     *
     * @return array<string, array<string, mixed>>
     */
    public function menuSettings(): array
    {
        $menu = $this->get('menu', []);

        return is_array($menu) ? $menu : [];
    }

    /**
     * The current chanda drive, last puja's spending and documents.
     *
     * @return array<string, mixed>
     */
    public function chanda(): array
    {
        $chanda = $this->get('chanda', []);

        return is_array($chanda) ? $chanda : [];
    }

    /**
     * Lanes and areas for "Where you live" (join, volunteer and chanda forms), in the committee's
     * order, then "Somewhere else". Value => name in the page's language. Until the committee
     * saves its own list, the sample lanes in FormOptions::AREAS.
     *
     * @return array<string, string>
     */
    public function areas(): array
    {
        $out = [];
        foreach ($this->areaList() as $area) {
            $out[$area['name']] = App::getLocale() === 'bn' && ($area['name_bn'] ?? '') !== '' ? (string) $area['name_bn'] : __($area['name']);
        }
        $out[self::ELSEWHERE] = __('Somewhere else');

        return $out;
    }

    /**
     * The lanes as the committee edits them (without "Somewhere else").
     *
     * @return list<array{name: string, name_bn: ?string}>
     */
    public function areaList(): array
    {
        $saved = $this->get('areas');

        if (is_array($saved) && $saved !== []) {
            return array_values(array_filter(array_map(
                fn ($a): ?array => is_array($a) && is_string($a['name'] ?? null) && $a['name'] !== '' ? ['name' => $a['name'], 'name_bn' => is_string($a['name_bn'] ?? null) ? $a['name_bn'] : null] : null,
                $saved,
            )));
        }

        $lanes = array_values(array_filter(array_keys(FormOptions::AREAS), fn (string $key): bool => $key !== self::ELSEWHERE));

        return array_map(fn (string $key): array => ['name' => $key, 'name_bn' => null], $lanes);
    }

    /**
     * Gallery days, newest first: each with an album key, a date, a title and photo/video items.
     *
     * @return list<array<string, mixed>>
     */
    public function galleryDays(): array
    {
        $gallery = $this->get('gallery', []);
        $days = is_array($gallery) && is_array($gallery['days'] ?? null) ? array_values($gallery['days']) : [];

        usort($days, fn (array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));

        return $days;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function galleryAlbums(): array
    {
        $gallery = $this->get('gallery', []);

        return is_array($gallery) && is_array($gallery['albums'] ?? null) ? array_values($gallery['albums']) : [];
    }

    public function isSample(): bool
    {
        return (bool) $this->get('sample_data', false);
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
