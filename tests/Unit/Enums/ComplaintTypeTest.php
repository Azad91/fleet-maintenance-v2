<?php

namespace Tests\Unit\Enums;

use App\Enums\ComplaintType;
use PHPUnit\Framework\TestCase;

class ComplaintTypeTest extends TestCase
{
    // ==================================================================
    // 1. ENUM VALUES
    // ==================================================================

    public function test_enum_has_exactly_three_cases(): void
    {
        $this->assertCount(3, ComplaintType::cases());
    }

    public function test_enum_values_match_database_strings(): void
    {
        $this->assertSame('accident', ComplaintType::Accident->value);
        $this->assertSame('breakdown', ComplaintType::Breakdown->value);
        $this->assertSame('maintenance', ComplaintType::Maintenance->value);
    }

    public function test_values_returns_all_values(): void
    {
        $this->assertSame(
            ['accident', 'breakdown', 'maintenance'],
            ComplaintType::values()
        );
    }

    // ==================================================================
    // 2. SEMANTIC CHECKS
    // ==================================================================

    public function test_is_accident(): void
    {
        $this->assertTrue(ComplaintType::Accident->isAccident());
        $this->assertFalse(ComplaintType::Breakdown->isAccident());
        $this->assertFalse(ComplaintType::Maintenance->isAccident());
    }

    public function test_is_breakdown(): void
    {
        $this->assertTrue(ComplaintType::Breakdown->isBreakdown());
        $this->assertFalse(ComplaintType::Accident->isBreakdown());
        $this->assertFalse(ComplaintType::Maintenance->isBreakdown());
    }

    public function test_is_maintenance(): void
    {
        $this->assertTrue(ComplaintType::Maintenance->isMaintenance());
        $this->assertFalse(ComplaintType::Accident->isMaintenance());
        $this->assertFalse(ComplaintType::Breakdown->isMaintenance());
    }

    // ==================================================================
    // 3. ICON
    // ==================================================================

    public function test_each_case_has_a_unique_icon(): void
    {
        $icons = array_map(
            static fn (ComplaintType $type) => $type->icon(),
            ComplaintType::cases()
        );

        $this->assertSame($icons, array_unique($icons));
    }

    public function test_icons_are_non_empty_strings(): void
    {
        foreach (ComplaintType::cases() as $case) {
            $this->assertIsString($case->icon());
            $this->assertNotEmpty($case->icon());
        }
    }

    // ==================================================================
    // 4. TRYFROM — safe parsing
    // ==================================================================

    public function test_tryfrom_returns_null_for_invalid_value(): void
    {
        $this->assertNull(ComplaintType::tryFrom('invalid'));
        $this->assertNull(ComplaintType::tryFrom(''));
        $this->assertNull(ComplaintType::tryFrom('ACCIDENT'));
    }

    public function test_tryfrom_returns_case_for_valid_value(): void
    {
        $this->assertSame(ComplaintType::Accident, ComplaintType::tryFrom('accident'));
        $this->assertSame(ComplaintType::Breakdown, ComplaintType::tryFrom('breakdown'));
        $this->assertSame(ComplaintType::Maintenance, ComplaintType::tryFrom('maintenance'));
    }
}
