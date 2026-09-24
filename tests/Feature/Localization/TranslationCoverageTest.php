<?php

namespace Tests\Feature\Localization;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards against missing translation keys.
 *
 * The application ships with four locales (en, az, tr, ru).
 *
 * STRICT LOCALES — en, az
 *   These two locales are the primary supported languages. Any key
 *   that exists in one but not the other is a hard failure. A missing
 *   key leaks the raw identifier into the UI (e.g. the user sees
 *   "messages.complaints.new" instead of "New Card"), which is
 *   unacceptable on a production deploy or in a customer demo.
 *
 * INFORMATIONAL LOCALES — tr, ru
 *   These locales are being brought up to parity. Their diff status
 *   is written to a diagnostic file (storage/logs/translation-coverage.log)
 *   so the translator can work through the backlog, but they do NOT
 *   fail the test suite. Once a locale reaches parity, it can be
 *   promoted to STRICT_LOCALES.
 *
 * English is the reference for all comparisons because it is the
 * fallback locale declared in config/app.php.
 */
class TranslationCoverageTest extends TestCase
{
    /**
     * Locales that must be perfectly in sync with English.
     * A missing or extra key here fails the test.
     */
    protected const STRICT_LOCALES = ['en', 'az'];

    /**
     * Locales that are still being translated. Their status is
     * logged but does not fail the test.
     */
    protected const INFORMATIONAL_LOCALES = ['tr', 'ru'];

    protected const REFERENCE_LOCALE = 'en';

    protected const LOG_PATH = 'logs/translation-coverage.log';

    // ==================================================================
    // 1. Every locale has all the same files as English
    // ==================================================================

    public function test_all_locales_have_same_translation_files(): void
    {
        $allLocales = array_merge(self::STRICT_LOCALES, self::INFORMATIONAL_LOCALES);
        $referenceFiles = $this->translationFiles(self::REFERENCE_LOCALE);

        foreach ($allLocales as $locale) {
            if ($locale === self::REFERENCE_LOCALE) {
                continue;
            }

            $localeFiles = $this->translationFiles($locale);

            $missing = array_diff($referenceFiles, $localeFiles);

            $this->assertEmpty(
                $missing,
                "Locale [{$locale}] is missing translation files: "
                .implode(', ', $missing)
            );
        }
    }

    // ==================================================================
    // 2. Strict locales have all top-level keys
    // ==================================================================

    public function test_strict_locales_have_same_top_level_keys(): void
    {
        $referenceKeys = $this->translationKeys(self::REFERENCE_LOCALE);
        $allMissing = [];

        foreach (self::STRICT_LOCALES as $locale) {
            if ($locale === self::REFERENCE_LOCALE) {
                continue;
            }

            $localeKeys = $this->translationKeys($locale);
            $missing = array_diff($referenceKeys, $localeKeys);

            if (! empty($missing)) {
                $allMissing[$locale] = array_values($missing);
            }
        }

        // Always assert — even when there is nothing missing — so the
        // test does not become "risky" for lack of assertions.
        $this->assertSame(
            [],
            $allMissing,
            $this->formatMissingReport($allMissing)
        );
    }

    // ==================================================================
    // 3. Strict locales have no extra keys
    // ==================================================================

    public function test_strict_locales_have_no_extra_keys(): void
    {
        $referenceKeys = $this->translationKeys(self::REFERENCE_LOCALE);

        foreach (self::STRICT_LOCALES as $locale) {
            if ($locale === self::REFERENCE_LOCALE) {
                continue;
            }

            $localeKeys = $this->translationKeys($locale);
            $extra = array_diff($localeKeys, $referenceKeys);

            $this->assertEmpty(
                $extra,
                "Locale [{$locale}] has ".count($extra).' keys that do not '
                ."exist in [{$locale}]'s reference (en):\n - "
                .implode("\n - ", array_slice($extra, 0, 20))
            );
        }
    }

    // ==================================================================
    // 4. Informational locales — log status, never fail
    // ==================================================================

    public function test_informational_locales_are_analyzed_without_failure(): void
    {
        $referenceKeys = $this->translationKeys(self::REFERENCE_LOCALE);
        $report = [];

        foreach (self::INFORMATIONAL_LOCALES as $locale) {
            $localeKeys = $this->translationKeys($locale);

            $missing = array_diff($referenceKeys, $localeKeys);
            $extra = array_diff($localeKeys, $referenceKeys);

            $report[$locale] = [
                'reference_keys' => count($referenceKeys),
                'locale_keys' => count($localeKeys),
                'missing' => count($missing),
                'extra' => count($extra),
                'missing_sample' => array_slice(array_values($missing), 0, 20),
                'extra_sample' => array_slice(array_values($extra), 0, 20),
            ];
        }

        // Write the report so translators have a worklist.
        $logPath = storage_path(self::LOG_PATH);
        $logDir = dirname($logPath);

        if (! is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $lines = [
            'Translation Coverage Report — '.now()->toIso8601String(),
            str_repeat('=', 70),
            '',
        ];

        foreach ($report as $locale => $data) {
            $lines[] = sprintf(
                '[%s] reference=%d, locale=%d, missing=%d, extra=%d',
                strtoupper($locale),
                $data['reference_keys'],
                $data['locale_keys'],
                $data['missing'],
                $data['extra']
            );

            if (! empty($data['missing_sample'])) {
                $lines[] = '  Missing (first 20):';
                foreach ($data['missing_sample'] as $key) {
                    $lines[] = '    - '.$key;
                }
            }

            if (! empty($data['extra_sample'])) {
                $lines[] = '  Extra (first 20):';
                foreach ($data['extra_sample'] as $key) {
                    $lines[] = '    + '.$key;
                }
            }

            $lines[] = '';
        }

        @file_put_contents($logPath, implode(PHP_EOL, $lines).PHP_EOL);

        // This test always passes — the report above is informational.
        // The assertion below just documents the intent: the test
        // writes a report and does not gate the build on locale parity.
        $this->assertTrue(true);

        // If you ever want to see the report in the test output,
        // uncomment the following line:
        // $this->markTestIncomplete('See storage/logs/translation-coverage.log for the backlog.');
    }

    // ==================================================================
    // HELPERS
    // ==================================================================

    /**
     * List every .php translation file in lang/{locale}/.
     *
     * @return array<int, string> e.g. ['auth.php', 'messages.php']
     */
    protected function translationFiles(string $locale): array
    {
        $path = lang_path($locale);

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($f) => $f->getExtension() === 'php')
            ->map(fn ($f) => $f->getFilename())
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Flatten every translation key in a locale into dot notation.
     *
     * Example:
     *   lang/en/messages.php with ['complaints' => ['new' => 'New']]
     *   → ['messages.complaints.new']
     *
     * @return array<int, string>
     */
    protected function translationKeys(string $locale): array
    {
        $path = lang_path($locale);

        if (! File::isDirectory($path)) {
            return [];
        }

        $keys = [];

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $namespace = $file->getFilenameWithoutExtension();
            $data = require $file->getPathname();

            if (! is_array($data)) {
                continue;
            }

            $this->flatten($data, $namespace, $keys);
        }

        sort($keys);

        return $keys;
    }

    /**
     * Recursively flatten a nested translation array into dot notation.
     *
     * @param  array<int|string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    protected function flatten(array $data, string $prefix, array &$keys): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix.'.'.$key;

            if (is_array($value)) {
                $this->flatten($value, $fullKey, $keys);
            } else {
                $keys[] = $fullKey;
            }
        }
    }

    /**
     * Format a "missing keys per locale" map for the assertion message.
     *
     * @param  array<string, array<int, string>>  $allMissing
     */
    protected function formatMissingReport(array $allMissing): string
    {
        if (empty($allMissing)) {
            return 'All strict locales are in sync with English.';
        }

        $lines = ['Missing translation keys detected:'];

        foreach ($allMissing as $locale => $keys) {
            $lines[] = sprintf('  [%s] — %d keys:', strtoupper($locale), count($keys));

            foreach (array_slice($keys, 0, 30) as $key) {
                $lines[] = '    - '.$key;
            }

            if (count($keys) > 30) {
                $lines[] = '    ... and '.(count($keys) - 30).' more';
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
