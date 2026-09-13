<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards against two families of dead-code / misplacement bugs:
 *
 * 1. Files under app/Http/Requests declaring a namespace that is NOT
 *    under App\Http\Requests — usually leftovers from incomplete
 *    refactors where a controller was supposed to be converted to a
 *    FormRequest but the old code was never removed.
 *
 * 2. Two files in the SAME namespace declaring the same class name —
 *    a genuine duplicate that Composer would silently pick one of,
 *    causing nondeterministic behavior depending on file discovery.
 *
 * NOTE: It is legal for Laravel applications to have classes with the
 * same short name in different namespaces (e.g. App\Http\Controllers\BusController
 * and App\Http\Controllers\Api\BusController). This test therefore
 * compares full namespace-qualified class names, not just the class
 * basename.
 */
class DeadCodeGuardTest extends TestCase
{
    /**
     * Every file under app/Http/Requests must declare a namespace
     * under App\Http\Requests. Any misplaced controller, model, or
     * service will fail the build.
     */
    public function test_requests_directory_only_contains_requests(): void
    {
        $offenders = [];

        foreach (File::allFiles(app_path('Http/Requests')) as $file) {
            $contents = File::get($file->getPathname());

            if (preg_match('/^namespace\s+([^;]+);/m', $contents, $matches)) {
                $namespace = trim($matches[1]);

                if (! str_starts_with($namespace, 'App\\Http\\Requests')) {
                    $relative = str_replace(
                        base_path().DIRECTORY_SEPARATOR,
                        '',
                        $file->getPathname()
                    );
                    $offenders[] = "{$relative} (namespace: {$namespace})";
                }
            }
        }

        $this->assertEmpty(
            $offenders,
            'Files under app/Http/Requests must declare a namespace '
            ."under App\\Http\\Requests. Misplaced files:\n - "
            .implode("\n - ", $offenders)
        );
    }

    /**
     * No two files under app/ may declare the SAME fully-qualified
     * class name. Different namespaces with the same short name are
     * perfectly fine (Laravel's standard Web/API/SuperAdmin split).
     */
    public function test_no_duplicate_fully_qualified_class_names_in_app_directory(): void
    {
        $byFqcn = [];

        foreach (File::allFiles(app_path()) as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $contents = File::get($file->getPathname());

            // Extract namespace (default to empty string if none).
            $namespace = '';
            if (preg_match('/^namespace\s+([^;]+);/m', $contents, $nsMatch)) {
                $namespace = trim($nsMatch[1]);
            }

            // Extract every class declared in the file.
            if (preg_match_all(
                '/\b(?:abstract\s+|final\s+)?class\s+(\w+)/',
                $contents,
                $classMatches
            )) {
                foreach ($classMatches[1] as $shortName) {
                    $fqcn = $namespace !== '' ? $namespace.'\\'.$shortName : $shortName;

                    $byFqcn[$fqcn][] = str_replace(
                        base_path().DIRECTORY_SEPARATOR,
                        '',
                        $file->getPathname()
                    );
                }
            }
        }

        $duplicates = array_filter(
            $byFqcn,
            fn ($paths) => count($paths) > 1
        );

        $this->assertEmpty(
            $duplicates,
            "Duplicate fully-qualified class names detected in app/:\n"
            .collect($duplicates)
                ->map(fn ($paths, $fqcn) => "  {$fqcn}:\n    - ".implode("\n    - ", $paths))
                ->implode("\n")
        );
    }

    /**
     * Guard against orphaned view files.
     *
     * A view is "orphaned" if no controller renders it via
     * view('reports.coming-soon') and no other view includes it via
     *
     * @include or @extends. Orphaned views bloat the repo, cause
     * confusion, and can hide missing features (a placeholder that
     * was never replaced).
     *
     * This check is intentionally conservative — it only inspects the
     * explicit "coming-soon" style placeholder view, whose name makes
     * the expectation clear. Adding a general orphan-view detector is
     * possible but prone to false positives (partials, layouts, and
     * dynamic view names are all legitimate).
     */
    public function test_coming_soon_placeholder_view_is_not_orphaned(): void
    {
        $viewPath = resource_path('views/reports/coming-soon.blade.php');

        $this->assertFileDoesNotExist(
            $viewPath,
            'The reports/coming-soon.blade.php placeholder was created as a '
            .'stub before the real report views existed. All reports are '
            .'now implemented, so the placeholder must not be reintroduced.'
        );
    }
}
