<?php

namespace Tests\Unit;

use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Fails when a language key exists in one of en / fr / ar but not in the others
 * (issue #34), when a key used in a view or controller does not exist in English,
 * or when a translation forgets a :placeholder the English text has.
 */
class LanguageParityTest extends TestCase
{
    private const LOCALES = ['en', 'fr', 'ar'];

    /** @return array<string, array<string, array<string, string>>> namespace.file => locale => flat key => text */
    private function catalogue(): array
    {
        $roots = ['' => dirname(__DIR__, 2).'/lang'];
        foreach (glob(dirname(__DIR__, 2).'/app/Modules/*', GLOB_ONLYDIR) as $module) {
            $roots[strtolower(basename($module)).'::'] = $module.'/lang';
        }

        $catalogue = [];
        foreach ($roots as $prefix => $dir) {
            foreach (self::LOCALES as $locale) {
                foreach (glob("$dir/$locale/*.php") ?: [] as $file) {
                    $group = $prefix.basename($file, '.php');
                    $catalogue[$group][$locale] = Arr::dot(require $file);
                }
            }
        }

        return $catalogue;
    }

    public function test_every_file_and_key_exists_in_all_three_languages(): void
    {
        foreach ($this->catalogue() as $group => $locales) {
            foreach (self::LOCALES as $locale) {
                $this->assertArrayHasKey($locale, $locales, "Language file [$group] is missing for [$locale].");
            }

            $all = array_unique(array_merge(...array_values(array_map('array_keys', $locales))));
            foreach ($all as $key) {
                foreach (self::LOCALES as $locale) {
                    $this->assertArrayHasKey($key, $locales[$locale], "[$group.$key] is missing in [$locale].");
                }
            }
        }
    }

    public function test_translations_keep_the_same_placeholders_as_english(): void
    {
        foreach ($this->catalogue() as $group => $locales) {
            foreach ($locales['en'] as $key => $text) {
                preg_match_all('/:([a-z_]+)/i', (string) $text, $english);

                foreach (['fr', 'ar'] as $locale) {
                    preg_match_all('/:([a-z_]+)/i', (string) $locales[$locale][$key], $other);
                    $this->assertEqualsCanonicalizing($english[1], $other[1], "[$group.$key] placeholders differ in [$locale].");
                }
            }
        }
    }

    public function test_no_translation_is_empty(): void
    {
        foreach ($this->catalogue() as $group => $locales) {
            foreach ($locales as $locale => $keys) {
                foreach ($keys as $key => $text) {
                    $this->assertNotSame('', trim((string) $text), "[$group.$key] is empty in [$locale].");
                }
            }
        }
    }

    public function test_every_literal_key_used_in_code_and_views_exists(): void
    {
        $catalogue = $this->catalogue();
        $root = dirname(__DIR__, 2);
        $files = [];
        foreach (['app', 'resources/views'] as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir", RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (preg_match('/\.(php|blade\.php)$/', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        $missing = [];
        foreach ($files as $path) {
            // Literal keys only: __('group.key') and __("group.key"); dynamic ones ("group.$x") are skipped.
            preg_match_all('/__\(\s*[\'"]((?:[a-z]+::)?[a-z_]+)\.([a-zA-Z0-9_]+(?:\.[a-zA-Z0-9_]+)*)[\'"]/', file_get_contents($path), $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                [$full, $group, $key] = $m;

                if (! isset($catalogue[$group]['en'])) {
                    $missing[] = "$group (file) used in ".basename($path);

                    continue;
                }

                // A key may name a whole array (for example common.languages) or a leaf.
                $leaf = isset($catalogue[$group]['en'][$key]);
                $branch = array_filter(array_keys($catalogue[$group]['en']), fn ($k) => str_starts_with($k, $key.'.'));
                if (! $leaf && ! $branch) {
                    $missing[] = "$group.$key used in ".basename($path);
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Translation keys used but not defined');
    }
}
