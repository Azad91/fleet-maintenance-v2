<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_pages_include_the_baseline_security_headers(): void
    {
        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }
}
