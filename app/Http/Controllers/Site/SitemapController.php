<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\NewsPost;
use App\Models\Notice;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * /sitemap.xml and /robots.txt for search engines.
 */
class SitemapController extends Controller
{
    /** Website pages listed in the sitemap (only routes that exist). */
    public const PAGES = [
        'home', 'events.index', 'notices.index', 'news.index', 'catering', 'nursery', 'gallery', 'chanda',
        'committee', 'contact', 'join', 'volunteer', 'panjika', 'privacy', 'terms',
    ];

    public function sitemap(): Response
    {
        $urls = [];

        foreach (self::PAGES as $name) {
            if (Route::has($name)) {
                $urls[] = ['loc' => route($name), 'lastmod' => null];
            }
        }

        foreach (Event::query()->published()->get() as $event) {
            $urls[] = ['loc' => route('events.show', $event), 'lastmod' => $event->updated_at?->toAtomString()];
        }

        foreach (Notice::query()->forPublic()->get() as $notice) {
            $urls[] = ['loc' => route('notices.show', $notice), 'lastmod' => $notice->updated_at?->toAtomString()];
        }

        foreach (NewsPost::query()->published()->get() as $post) {
            $urls[] = ['loc' => route('news.show', $post), 'lastmod' => $post->updated_at?->toAtomString()];
        }

        return response()
            ->view('site.sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /app',
            'Disallow: /admin',
            'Disallow: /lang/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
