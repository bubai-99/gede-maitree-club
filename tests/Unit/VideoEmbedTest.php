<?php

namespace Tests\Unit;

use App\Support\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, bool}>
     */
    public static function links(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1m5s', 'iframe', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&start=65', false],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', 'iframe', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', false],
            'youtube shorts' => ['https://youtube.com/shorts/abcDEF12345', 'iframe', 'https://www.youtube-nocookie.com/embed/abcDEF12345?rel=0', true],
            'youtube on a phone' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'iframe', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', false],
            'facebook video' => ['https://www.facebook.com/gedemaitreeclub/videos/1234567890/', 'iframe', 'https://www.facebook.com/plugins/video.php?show_text=false&href=https%3A%2F%2Fwww.facebook.com%2Fgedemaitreeclub%2Fvideos%2F1234567890%2F', false],
            'facebook reel' => ['https://www.facebook.com/reel/987654321', 'iframe', 'https://www.facebook.com/plugins/video.php?show_text=false&href=https%3A%2F%2Fwww.facebook.com%2Freel%2F987654321', true],
            'fb.watch' => ['https://fb.watch/abcD3fGh/', 'iframe', 'https://www.facebook.com/plugins/video.php?show_text=false&href=https%3A%2F%2Ffb.watch%2FabcD3fGh%2F', false],
            'instagram reel' => ['https://www.instagram.com/reel/C1a2B3c4D5e/?igsh=x', 'iframe', 'https://www.instagram.com/reel/C1a2B3c4D5e/embed', true],
            'vimeo' => ['https://vimeo.com/76979871', 'iframe', 'https://player.vimeo.com/video/76979871', false],
            'google drive' => ['https://drive.google.com/file/d/1AbCdEfGhIjK/view?usp=sharing', 'iframe', 'https://drive.google.com/file/d/1AbCdEfGhIjK/preview', false],
            'dailymotion' => ['https://www.dailymotion.com/video/x8abcd1', 'iframe', 'https://www.dailymotion.com/embed/video/x8abcd1', false],
            'mp4 file' => ['https://example.org/clips/sports-day.mp4', 'video', 'https://example.org/clips/sports-day.mp4', false],
            'anything else' => ['https://example.org/some/page', 'link', 'https://example.org/some/page', false],
        ];
    }

    #[DataProvider('links')]
    public function test_links_become_players(string $url, string $type, string $src, bool $tall): void
    {
        $embed = VideoEmbed::from($url);

        $this->assertNotNull($embed);
        $this->assertSame($type, $embed->type);
        $this->assertSame($src, $embed->src);
        $this->assertSame($tall, $embed->tall);
    }

    public function test_things_that_are_not_links_are_ignored(): void
    {
        $this->assertNull(VideoEmbed::from('not a link'));
        $this->assertNull(VideoEmbed::from('javascript:alert(1)'));
        $this->assertNull(VideoEmbed::from('ftp://example.org/video.mp4'));
    }
}
