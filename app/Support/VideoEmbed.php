<?php

namespace App\Support;

/**
 * Turns a video link the committee pastes into something a page can show: an embedded player
 * for YouTube, Facebook, Instagram, Vimeo, Google Drive and Dailymotion, a <video> for a direct
 * .mp4 / .webm / .ogg file, or (for anything else) a plain link card, so a link never breaks
 * the page. Adding a site is one more match in from().
 */
final class VideoEmbed
{
    /**
     * @param  'iframe'|'video'|'link'  $type
     * @param  bool  $tall  phone-shaped video (Shorts, Reels): 9:16 instead of 16:9
     */
    private function __construct(
        public readonly string $type,
        public readonly string $src,
        public readonly string $provider,
        public readonly string $url,
        public readonly bool $tall = false,
    ) {}

    public static function from(string $url): ?self
    {
        $url = trim($url);

        if (! preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower(preg_replace('/^(www\.|m\.|mobile\.)/', '', (string) ($parts['host'] ?? '')) ?? '');
        $path = (string) ($parts['path'] ?? '');
        parse_str((string) ($parts['query'] ?? ''), $query);

        // YouTube: watch?v=, youtu.be/, /shorts/, /live/, /embed/
        if (in_array($host, ['youtube.com', 'youtu.be', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            $id = null;
            $tall = false;
            if ($host === 'youtu.be') {
                $id = trim($path, '/');
            } elseif (is_string($query['v'] ?? null)) {
                $id = $query['v'];
            } elseif (preg_match('#^/(shorts|live|embed)/([^/?]+)#', $path, $m)) {
                $id = $m[2];
                $tall = $m[1] === 'shorts';
            }
            if ($id !== null && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
                $start = self::seconds($query['t'] ?? $query['start'] ?? null);

                return new self('iframe', 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0'.($start ? '&start='.$start : ''), 'YouTube', $url, $tall);
            }
        }

        // Facebook: /videos/, /watch/?v=, /reel/, /share/v/, fb.watch
        if (in_array($host, ['facebook.com', 'fb.watch', 'fb.com', 'web.facebook.com'], true)) {
            if ($host === 'fb.watch' || str_contains($path, '/videos/') || str_starts_with($path, '/watch') || str_starts_with($path, '/reel') || str_starts_with($path, '/share/v/') || str_starts_with($path, '/share/r/')) {
                $tall = str_starts_with($path, '/reel') || str_starts_with($path, '/share/r/');

                return new self('iframe', 'https://www.facebook.com/plugins/video.php?show_text=false&href='.rawurlencode($url), 'Facebook', $url, $tall);
            }
        }

        // Instagram: /p/, /reel/, /tv/
        if ($host === 'instagram.com' && preg_match('#^/(p|reel|reels|tv)/([A-Za-z0-9_-]+)#', $path, $m)) {
            return new self('iframe', 'https://www.instagram.com/'.($m[1] === 'reels' ? 'reel' : $m[1]).'/'.$m[2].'/embed', 'Instagram', $url, true);
        }

        // Vimeo: vimeo.com/123456 or player.vimeo.com/video/123456
        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true) && preg_match('#/(?:video/)?(\d{5,})#', $path, $m)) {
            return new self('iframe', 'https://player.vimeo.com/video/'.$m[1], 'Vimeo', $url);
        }

        // Google Drive: drive.google.com/file/d/ID/...
        if ($host === 'drive.google.com' && preg_match('#/file/d/([A-Za-z0-9_-]+)#', $path, $m)) {
            return new self('iframe', 'https://drive.google.com/file/d/'.$m[1].'/preview', 'Google Drive', $url);
        }

        // Dailymotion: dailymotion.com/video/ID, dai.ly/ID
        if (in_array($host, ['dailymotion.com', 'dai.ly'], true) && preg_match('#^/(?:video/)?([A-Za-z0-9]+)#', $path, $m)) {
            return new self('iframe', 'https://www.dailymotion.com/embed/video/'.$m[1], 'Dailymotion', $url);
        }

        // A video file anywhere
        if (preg_match('/\.(mp4|webm|ogg|ogv|m4v|mov)$/i', $path)) {
            return new self('video', $url, 'Video', $url);
        }

        return new self('link', $url, $host !== '' ? $host : 'Video', $url);
    }

    /**
     * "90", "1m30s", "1h2m3s" → seconds.
     */
    private static function seconds(mixed $t): int
    {
        if (! is_string($t) || $t === '') {
            return 0;
        }
        if (ctype_digit($t)) {
            return (int) $t;
        }
        preg_match('/(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/', $t, $m);

        return ((int) ($m[1] ?? 0)) * 3600 + ((int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
    }
}
