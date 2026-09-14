<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class RequestIdMiddlewareTest extends TestCase
{
    // ==================================================================
    // 1. VALID UUID IS PRESERVED
    // ==================================================================

    public function test_valid_uuid_header_is_preserved(): void
    {
        $uuid = (string) Str::uuid();

        $response = $this->withHeaders(['X-Request-ID' => $uuid])
            ->get('/');

        $this->assertSame(
            $uuid,
            $response->headers->get('X-Request-ID'),
            'A valid UUID must be preserved across the request'
        );
    }

    // ==================================================================
    // 2. MALFORMED HEADER IS REPLACED
    // ==================================================================

    public function test_malformed_header_is_replaced_with_uuid(): void
    {
        $response = $this->withHeaders(['X-Request-ID' => 'not-a-uuid'])
            ->get('/');

        $returned = $response->headers->get('X-Request-ID');

        $this->assertNotSame('not-a-uuid', $returned);
        $this->assertTrue(
            Str::isUuid($returned),
            'A malformed X-Request-ID must be replaced with a valid UUID'
        );
    }

    public function test_empty_header_is_replaced_with_uuid(): void
    {
        $response = $this->withHeaders(['X-Request-ID' => ''])
            ->get('/');

        $this->assertTrue(Str::isUuid($response->headers->get('X-Request-ID')));
    }

    // ==================================================================
    // 3. MISSING HEADER GENERATES NEW UUID
    // ==================================================================

    public function test_missing_header_generates_uuid(): void
    {
        $response = $this->get('/');

        $returned = $response->headers->get('X-Request-ID');

        $this->assertNotNull($returned);
        $this->assertTrue(Str::isUuid($returned));
    }

    // ==================================================================
    // 4. LOG INJECTION PAYLOADS ARE REJECTED
    // ==================================================================

    public function test_log_injection_attempt_is_rejected(): void
    {
        // An attacker tries to poison every log line by sending a
        // value that looks like a structured log entry.
        $malicious = 'fake-id; user_id=1; admin=true';

        $response = $this->withHeaders(['X-Request-ID' => $malicious])
            ->get('/');

        $returned = $response->headers->get('X-Request-ID');

        $this->assertNotSame($malicious, $returned);
        $this->assertTrue(Str::isUuid($returned));
    }

    public function test_newline_injection_attempt_is_rejected(): void
    {
        $malicious = "abc\r\nX-Injected: header";

        $response = $this->withHeaders(['X-Request-ID' => $malicious])
            ->get('/');

        $returned = $response->headers->get('X-Request-ID');

        $this->assertNotSame($malicious, $returned);
        $this->assertTrue(Str::isUuid($returned));
        $this->assertStringNotContainsString("\r", $returned);
        $this->assertStringNotContainsString("\n", $returned);
    }
}
