<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Text the committee writes for a news post: Markdown (headings, bold, lists, links, quotes), with
 * any HTML typed in removed, and a video link on a line of its own shown as the video itself.
 */
final class RichText
{
    public static function render(?string $markdown): HtmlString
    {
        if ($markdown === null || trim($markdown) === '') {
            return new HtmlString('');
        }

        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        // <p>https://…</p> or <p><a href="https://…">https://…</a></p> → the player
        $html = preg_replace_callback(
            '#<p>\s*(?:<a href="([^"]+)">[^<]*</a>|(https?://[^\s<]+))\s*</p>#',
            function (array $m): string {
                $url = html_entity_decode($m[1] !== '' ? $m[1] : $m[2], ENT_QUOTES | ENT_HTML5);
                $embed = VideoEmbed::from($url);

                return $embed !== null && $embed->type !== 'link'
                    ? view('partials.video-embed', ['embed' => $embed, 'caption' => null])->render()
                    : $m[0];
            },
            $html,
        ) ?? $html;

        return new HtmlString($html);
    }
}
