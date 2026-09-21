<?php

namespace Tests\Unit\OilChange;

use App\Models\Bus;
use Tests\TestCase;

class OilIntervalResolutionTest extends TestCase
{
    public function test_motor_interval_for_12m_bus(): void
    {
        $bus = new Bus(['uzunluq' => 12]);
        $this->assertSame(36000, $bus->motorOilIntervalKm());
    }

    public function test_motor_interval_for_18m_bus(): void
    {
        $bus = new Bus(['uzunluq' => 18]);
        $this->assertSame(30000, $bus->motorOilIntervalKm());
    }

    public function test_motor_interval_defaults_to_12m_when_uzunluq_null(): void
    {
        $bus = new Bus;
        $this->assertSame(36000, $bus->motorOilIntervalKm());
    }

    public function test_axle_interval_is_fixed(): void
    {
        $bus = new Bus;
        $this->assertSame(180000, $bus->axleOilIntervalKm());
    }

    public function test_gearbox_interval_for_shell(): void
    {
        $this->assertSame(180000, Bus::gearboxIntervalForBrand('SHELL'));
    }

    public function test_gearbox_interval_for_luk(): void
    {
        $this->assertSame(120000, Bus::gearboxIntervalForBrand('LUK'));
    }

    public function test_gearbox_interval_falls_back_for_unknown_brand(): void
    {
        $this->assertSame(180000, Bus::gearboxIntervalForBrand('UNKNOWN'));
    }

    public function test_gearbox_interval_falls_back_for_null_brand(): void
    {
        $this->assertSame(180000, Bus::gearboxIntervalForBrand(null));
    }
}
