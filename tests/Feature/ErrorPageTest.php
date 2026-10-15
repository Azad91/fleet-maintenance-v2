<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_404_page_renders_custom_view(): void
    {
        $response = $this->get('/this-route-does-not-exist-12345');
        $response->assertStatus(404);
        $response->assertSee(__('messages.errors.not_found_title'));
    }

    public function test_419_page_renders_custom_view(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-419-error', function () {
            abort(419);
        });

        $response = $this->get('/test-419-error');
        $response->assertStatus(419);
        $response->assertSee(__('messages.errors.session_expired_title'));
    }
}
