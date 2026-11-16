<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Facades\App;

/**
 * Editable page text (config/pages.php): what the committee published in the
 * console, or the default text in the page's language.
 */
class PageContent
{
    /** @var array<string, Page|null> */
    private array $pages = [];

    public function page(string $key): ?Page
    {
        if (! array_key_exists($key, $this->pages)) {
            $this->pages[$key] = Page::query()->where('key', $key)->first();
        }

        return $this->pages[$key];
    }

    public function text(string $page, string $field): string
    {
        $blocks = $this->page($page)->blocks ?? [];
        $bengali = App::getLocale() === 'bn';

        $saved = $blocks[$bengali ? $field.'_bn' : $field] ?? null;

        if (is_string($saved) && trim($saved) !== '') {
            return $saved;
        }

        return __((string) config("pages.{$page}.fields.{$field}.default", ''));
    }

    /**
     * Banner photo URL: an uploaded photo, or the page's sample photo.
     */
    public function photoUrl(string $page, int $width = 1800): ?string
    {
        $uploaded = $this->page($page)?->getFirstMediaUrl('photo');

        if (is_string($uploaded) && $uploaded !== '') {
            return $uploaded;
        }

        $key = $this->samplePhotoKey($page);

        return $key !== null ? Photo::url($key, $width) : null;
    }

    /**
     * The sample (Unsplash) photo key when no photo has been uploaded, for its credit.
     */
    public function samplePhotoKey(string $page): ?string
    {
        if ($this->page($page)?->hasUploadedPhoto()) {
            return null;
        }

        $key = config("pages.{$page}.photo");

        return is_string($key) && Photo::exists($key) ? $key : null;
    }

    public function photoAlt(string $page): string
    {
        $key = $this->samplePhotoKey($page);

        return $key !== null ? Photo::alt($key) : '';
    }
}
