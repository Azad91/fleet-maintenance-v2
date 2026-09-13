<?php

namespace Tests\Feature\Translations;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards against a family of bugs where a translation key is written
 * with the wrong namespace — most notably `messages.enums.X` which
 * does not exist. The correct namespace is `enums.X`.
 *
 * A wrong namespace causes raw keys to render in the UI, so the user
 * sees something like "messages.enums.complaint_status.pending"
 * instead of "Pending".
 *
 * See the lang/{locale}/enums.php files for the source of truth.
 */
class NoMissingMessagesEnumsTest extends TestCase
{
    /**
     * Recursively collect every .blade.php file under resources/views.
     *
     * @return array<int, string>
     */
    private function bladeFiles(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->map(fn ($file) => $file->getPathname())
            ->values()
            ->all();
    }

    public function test_no_blade_file_references_messages_enums_namespace(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $path) {
            $contents = File::get($path);

            // Look for the wrong namespace in any form of string literal.
            if (preg_match('/messages\.enums\./', $contents)) {
                $offenders[] = str_replace(
                    base_path() . DIRECTORY_SEPARATOR,
                    '',
                    $path
                );
            }
        }

        $this->assertEmpty(
            $offenders,
            "The 'messages.enums.*' namespace does not exist. "
            . "Use 'enums.*' instead. "
            . "Offending files:\n - " . implode("\n - ", $offenders)
        );
    }

    public function test_enums_namespace_actually_resolves(): void
    {
        // Sanity check: the keys we expect must exist for every locale.
        $locales = array_keys(config('app.supported_locales', ['en']));

        foreach ($locales as $locale) {
            app()->setLocale($locale);

            $this->assertNotEquals(
                'enums.complaint_status.pending',
                __('enums.complaint_status.pending'),
                "Translation for `enums.complaint_status.pending` is missing in locale [{$locale}]."
            );

            $this->assertNotEquals(
                'enums.location.road',
                __('enums.location.road'),
                "Translation for `enums.location.road` is missing in locale [{$locale}]."
            );

            $this->assertNotEquals(
                'enums.complaint_type.breakdown',
                __('enums.complaint_type.breakdown'),
                "Translation for `enums.complaint_type.breakdown` is missing in locale [{$locale}]."
            );
        }
    }
}
