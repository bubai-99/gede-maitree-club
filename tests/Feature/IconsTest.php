<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * resources/js/icons.js only bundles the Lucide icons the views use, so every
 * icon a view or navigation list names must be registered there.
 */
class IconsTest extends TestCase
{
    public function test_every_icon_used_is_registered(): void
    {
        $registered = File::get(resource_path('js/icons.js'));
        $missing = [];

        foreach ($this->usedIcons() as $icon) {
            $name = Str::studly($icon);

            if (! preg_match('/\b'.preg_quote($name, '/').',/', $registered)) {
                $missing[] = $icon;
            }
        }

        $this->assertSame([], $missing, 'Add these icons to resources/js/icons.js: '.implode(', ', $missing));
    }

    /**
     * @return list<string>
     */
    private function usedIcons(): array
    {
        $icons = [];

        foreach ([resource_path('views'), app_path()] as $dir) {
            foreach (File::allFiles($dir) as $file) {
                preg_match_all('/<x-icon\s+name="([a-z0-9-]+)"|\'icon\'\s*=>\s*\'([a-z0-9-]+)\'/', $file->getContents(), $matches);
                $icons = array_merge($icons, array_filter($matches[1]), array_filter($matches[2]));
            }
        }

        return array_values(array_unique($icons));
    }
}
