<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Public registration is intentionally disabled in this application.
 *
 * Users are created exclusively by administrators through the
 * SuperAdmin panel or the per-garage user management screen. The
 * /register route does not exist, and no RegisteredUserController
 * should ever be reintroduced without an explicit security review.
 *
 * This test file replaces the old RegistrationTest which was passing
 * for the wrong reason: it asserted a 404 (route missing) but the
 * accompanying controller was actually a dead abort(403) stub that
 * was never reachable.
 */
class RegistrationDisabledTest extends TestCase
{
    // ==================================================================
    // 1. ROUTE DOES NOT EXIST
    // ==================================================================

    public function test_register_route_is_not_registered(): void
    {
        $this->assertFalse(
            Route::has('register'),
            "The 'register' named route must not exist. "
            . "Public registration is disabled by design."
        );
    }

    public function test_get_register_returns_404(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_post_register_returns_404(): void
    {
        $this->post('/register', [
            'name'                  => 'Attacker',
            'email'                 => 'attacker@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();
    }

    // ==================================================================
    // 2. NO USER IS CREATED FROM A REGISTER ATTEMPT
    // ==================================================================

    public function test_register_attempt_does_not_create_a_user(): void
    {
        $this->post('/register', [
            'name'                  => 'Attacker',
            'email'                 => 'attacker@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'attacker@test.com']);
    }

    // ==================================================================
    // 3. DEAD CONTROLLER IS GONE
    // ==================================================================

    public function test_registered_user_controller_does_not_exist(): void
    {
        $path = app_path('Http/Controllers/Auth/RegisteredUserController.php');

        $this->assertFileDoesNotExist(
            $path,
            'The RegisteredUserController was a dead abort(403) stub with '
            . 'no route attached. It must not be reintroduced — user '
            . 'creation belongs to SuperAdmin\\UserController and '
            . 'UserManagementController.'
        );
    }

    // ==================================================================
    // 4. NO BLADE FILE LINKS TO REGISTER
    // ==================================================================

    public function test_no_blade_file_references_register_route(): void
    {
        $offenders = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = File::get($file->getPathname());

            // Match both route('register') and url('/register') forms.
            if (preg_match("/route\(\s*['\"]register['\"]/", $contents)
                || preg_match("/url\(\s*['\"]\/?register['\"]/", $contents)) {
                $offenders[] = str_replace(
                    base_path() . DIRECTORY_SEPARATOR,
                    '',
                    $file->getPathname()
                );
            }
        }

        $this->assertEmpty(
            $offenders,
            "These blade files link to the non-existent register route:\n - "
            . implode("\n - ", $offenders)
        );
    }

    // ==================================================================
    // 5. WELCOME PAGE HAS NO REGISTER LINK
    // ==================================================================

    public function test_welcome_page_has_no_register_link(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('/register', false);
        $response->assertDontSee('register"', false);
    }
        // ==================================================================
    // 6. REGISTER VIEW IS GONE
    // ==================================================================

    public function test_register_view_does_not_exist(): void
    {
        $path = resource_path('views/auth/register.blade.php');

        $this->assertFileDoesNotExist(
            $path,
            'The register.blade.php view had no controller and no route, '
            . 'yet still referenced route(\'register\'). It must not be '
            . 'reintroduced — public registration is disabled by design.'
        );
    }
}
