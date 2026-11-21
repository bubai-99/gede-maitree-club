<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

/**
 * The nursery's lists the committee edits (Settings → Plant lists): plant types (the chips on the
 * nursery page), how much sun and how much water. Each choice has a short key that plants keep
 * (so renaming a choice never touches the plants) and a name in English and Bengali.
 * Until the committee saves its own lists, the defaults below.
 */
final class PlantLists
{
    public const KINDS = ['category', 'light', 'water'];

    /** @var array<string, array<string, string>> kind => [key => English name] */
    private const DEFAULTS = [
        'category' => ['flowering' => 'Flowering', 'seasonal' => 'Winter seasonal', 'fruit' => 'Fruit', 'medicinal' => 'Medicinal', 'indoor' => 'Indoor'],
        'light' => ['sun' => 'Full sun', 'part' => 'Some sun', 'shade' => 'Indoor light'],
        'water' => ['often' => 'Water daily', 'moderate' => 'Every 2–3 days', 'little' => 'Once a week'],
    ];

    public function __construct(private readonly SiteSettings $site) {}

    /**
     * The choices as the committee edits them, in order.
     *
     * @return list<array{key: string, name: string, name_bn: ?string}>
     */
    public function items(string $kind): array
    {
        $saved = $this->site->get('plant_lists');
        $list = is_array($saved) && is_array($saved[$kind] ?? null) ? $saved[$kind] : null;

        if ($list !== null && $list !== []) {
            $out = [];
            foreach ($list as $row) {
                if (is_array($row) && is_string($row['key'] ?? null) && is_string($row['name'] ?? null) && $row['name'] !== '') {
                    $out[] = ['key' => $row['key'], 'name' => $row['name'], 'name_bn' => is_string($row['name_bn'] ?? null) && $row['name_bn'] !== '' ? $row['name_bn'] : null];
                }
            }

            return $out;
        }

        $out = [];
        foreach (self::DEFAULTS[$kind] ?? [] as $key => $name) {
            $bn = trans($name, [], 'bn');
            $out[] = ['key' => $key, 'name' => $name, 'name_bn' => is_string($bn) && $bn !== $name ? $bn : null];
        }

        return $out;
    }

    /**
     * key => name in the page's language, for select boxes and chips.
     *
     * @return array<string, string>
     */
    public function options(string $kind): array
    {
        $bengali = App::getLocale() === 'bn';
        $out = [];
        foreach ($this->items($kind) as $item) {
            $out[$item['key']] = $bengali && $item['name_bn'] !== null ? $item['name_bn'] : __($item['name']);
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function keys(string $kind): array
    {
        return array_column($this->items($kind), 'key');
    }

    /**
     * A choice's name in the page's language; null when the key is empty or no longer in the list.
     */
    public function label(string $kind, ?string $key): ?string
    {
        return $key !== null && $key !== '' ? ($this->options($kind)[$key] ?? null) : null;
    }
}
