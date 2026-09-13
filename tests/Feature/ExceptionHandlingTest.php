<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Verifies that after removing the redundant exception renderers
 * in C5, the following still behave correctly:
 *
 *   1. Laravel's built-in exception handling produces the expected
 *      status codes and JSON shape (validation, auth, 404, 403).
 *   2. The project's own custom exception handlers continue to run.
 *   3. The removed renderers are not silently re-added.
 */
class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    // ==================================================================
    // 1. LARAVEL DEFAULTS — regression guards after C5 cleanup
    // ==================================================================

    public function test_validation_errors_return_422_with_laravel_shape(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['email', 'password'],
            ]);
    }

    public function test_unauthenticated_api_request_returns_401(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_unknown_api_route_returns_404(): void
    {
        $this->getJson('/api/this-route-does-not-exist')
            ->assertStatus(404);
    }

    public function test_unauthenticated_web_request_redirects_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_unauthorized_web_request_returns_403(): void
    {
        // The /super-admin/* routes are protected by the SuperAdmin
        // middleware. To isolate that middleware we bypass the
        // garage.selected guard, which would otherwise redirect this
        // user before the SuperAdmin check ever runs.
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureGarageSelected::class,
        ]);

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/super-admin/dashboard')
            ->assertStatus(403);
    }

    // ==================================================================
    // 2. CUSTOM HANDLERS ARE STILL REGISTERED
    // ==================================================================

    /**
     * Custom exception renderers live in bootstrap/app.php. This test
     * asserts that the handlers for our three custom exceptions are
     * still present. The actual runtime behavior of each handler is
     * covered by dedicated test files:
     *   - GarageAccessDeniedException      → covered implicitly via
     *                                        middleware tests
     *   - StockInsufficientException       → ComplaintStockTest
     *   - MissingGarageContextException    → MissingGarageContextTest
     */
    public function test_bootstrap_app_keeps_custom_exception_renderers(): void
    {
        $contents = File::get(base_path('bootstrap/app.php'));

        // Custom handlers that must remain.
        $this->assertStringContainsString(
            'GarageAccessDeniedException',
            $contents,
            'GarageAccessDeniedException handler must remain in bootstrap/app.php'
        );
        $this->assertStringContainsString(
            'StockInsufficientException',
            $contents,
            'StockInsufficientException handler must remain in bootstrap/app.php'
        );
        $this->assertStringContainsString(
            'MissingGarageContextException',
            $contents,
            'MissingGarageContextException handler must remain in bootstrap/app.php'
        );
    }

    /**
     * After C5 cleanup, the only renderers in bootstrap/app.php should
     * be the ones that carry project-specific behavior. The following
     * are handled by Laravel out of the box and must not reappear as
     * custom closures.
     *
     * The match strings target the render-closure parameter signature
     * so the reportable() closure — which still references the same
     * class names — does not trigger a false positive.
     */
    public function test_bootstrap_app_has_no_redundant_exception_renderers(): void
    {
        $contents = File::get(base_path('bootstrap/app.php'));

        $forbidden = [
            'ValidationException $e, Request $request',
            'ModelNotFoundException $e, Request $request',
            'NotFoundHttpException $e, Request $request',
            'AuthenticationException $e, Request $request',
            'AuthorizationException $e, Request $request',
        ];

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $contents,
                "Redundant exception renderer detected: '{$needle}'. "
                . 'Laravel 11+ handles this exception correctly out of the box.'
            );
        }
    }
}
