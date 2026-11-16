<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Every English string the app shows must have a Bengali translation.
 */
class TranslationsTest extends TestCase
{
    public function test_every_translation_key_has_a_bengali_translation(): void
    {
        /** @var array<string, string> $bengali */
        $bengali = json_decode(File::get(lang_path('bn.json')), true, flags: JSON_THROW_ON_ERROR);

        $missing = [];

        foreach ($this->usedKeys() as $key => $file) {
            if (! array_key_exists($key, $bengali) || trim($bengali[$key]) === '') {
                $missing[] = "{$key}  ({$file})";
            }
        }

        $this->assertSame([], $missing, "Missing Bengali translations:\n".implode("\n", $missing));
    }

    /**
     * @return array<string, string> key => first file that uses it
     */
    private function usedKeys(): array
    {
        $keys = [];
        $patterns = [
            "/__\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/",
            "/'(?:label|group)'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/",
            // Enum labels: self::Case => 'English label'
            "/self::\\w+\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/",
        ];

        foreach ([resource_path('views'), app_path()] as $dir) {
            foreach (File::allFiles($dir) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = $file->getContents();

                foreach ($patterns as $pattern) {
                    preg_match_all($pattern, $contents, $matches);

                    foreach ($matches[1] as $raw) {
                        $key = str_replace(["\\'", '\\\\'], ["'", '\\'], $raw);

                        // Keys of group files (validation.required, passwords.reset) are not JSON keys.
                        if (preg_match('/^[a-z_]+\.[a-z_.]+$/', $key)) {
                            continue;
                        }

                        $keys[$key] ??= $file->getRelativePathname();
                    }
                }
            }
        }

        return $keys;
    }
}
