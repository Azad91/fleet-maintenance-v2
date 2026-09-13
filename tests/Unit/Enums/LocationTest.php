<?php

namespace Tests\Unit\Enums;

use App\Enums\Location;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    // ==================================================================
    // 1. ENUM VALUES
    // ==================================================================

    public function test_enum_has_exactly_two_cases(): void
    {
        $this->assertCount(2, Location::cases());
    }

    public function test_enum_values_match_database_strings(): void
    {
        $this->assertSame('road', Location::Road->value);
        $this->assertSame('garage', Location::Garage->value);
    }

    public function test_values_returns_all_values(): void
    {
        $this->assertSame(['road', 'garage'], Location::values());
    }

    // ==================================================================
    // 2. SEMANTIC CHECKS
    // ==================================================================

    public function test_is_road(): void
    {
        $this->assertTrue(Location::Road->isRoad());
        $this->assertFalse(Location::Garage->isRoad());
    }

    public function test_is_garage(): void
    {
        $this->assertTrue(Location::Garage->isGarage());
        $this->assertFalse(Location::Road->isGarage());
    }

    public function test_road_requires_driver(): void
    {
        $this->assertTrue(Location::Road->requiresDriver());
        $this->assertFalse(Location::Garage->requiresDriver());
    }

    public function test_road_requires_reported_time(): void
    {
        $this->assertTrue(Location::Road->requiresReportedTime());
        $this->assertFalse(Location::Garage->requiresReportedTime());
    }

    // ==================================================================
    // 3. ICON
    // ==================================================================

    public function test_each_case_has_a_unique_icon(): void
    {
        $icons = array_map(
            static fn (Location $loc) => $loc->icon(),
            Location::cases()
        );

        $this->assertSame($icons, array_unique($icons));
    }

    public function test_icons_are_non_empty_strings(): void
    {
        foreach (Location::cases() as $case) {
            $this->assertIsString($case->icon());
            $this->assertNotEmpty($case->icon());
        }
    }

    // ==================================================================
    // 4. TRYFROM — safe parsing
    // ==================================================================

    public function test_tryfrom_returns_null_for_invalid_value(): void
    {
        $this->assertNull(Location::tryFrom('invalid'));
        $this->assertNull(Location::tryFrom(''));
        $this->assertNull(Location::tryFrom('ROAD'));
        $this->assertNull(Location::tryFrom('yol')); // legacy AZ value
    }

    public function test_tryfrom_returns_case_for_valid_value(): void
    {
        $this->assertSame(Location::Road, Location::tryFrom('road'));
        $this->assertSame(Location::Garage, Location::tryFrom('garage'));
    }
}
