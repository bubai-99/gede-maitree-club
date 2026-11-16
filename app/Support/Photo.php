<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

/**
 * Sample photos from config/photos.php (Unsplash, hotlinked and credited).
 */
final class Photo
{
    public static function exists(?string $key): bool
    {
        return $key !== null && config("photos.{$key}") !== null;
    }

    public static function url(string $key, int $width = 1600): string
    {
        $id = (string) config("photos.{$key}.id");

        return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$width}&q=70";
    }

    public static function srcset(string $key): string
    {
        return collect([640, 1200, 1800])
            ->map(fn (int $w): string => self::url($key, $w)." {$w}w")
            ->implode(', ');
    }

    public static function alt(string $key): string
    {
        $field = App::getLocale() === 'bn' ? 'alt_bn' : 'alt';

        return (string) (config("photos.{$key}.{$field}") ?? config("photos.{$key}.alt"));
    }

    public static function by(string $key): string
    {
        return (string) config("photos.{$key}.by");
    }

    public static function page(string $key): string
    {
        return (string) config("photos.{$key}.page");
    }
}
