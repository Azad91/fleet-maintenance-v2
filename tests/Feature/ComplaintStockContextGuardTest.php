<?php

namespace Tests\Feature;

use App\Exceptions\MissingGarageContextException;
use App\Services\Complaint\ComplaintStockService;
use App\Services\GarageContext;
use Tests\TestCase;

class ComplaintStockContextGuardTest extends TestCase
{
    // No RefreshDatabase — these tests only exercise the guard, no
    // database access occurs before the exception is thrown.

    protected function setUp(): void
    {
        parent::setUp();
        GarageContext::clear();
        session()->forget(['current_garage_id', 'current_company_id']);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    public function test_deduct_stock_fails_closed_without_garage_context(): void
    {
        $this->expectException(MissingGarageContextException::class);

        app(ComplaintStockService::class)->deductStock([
            ['code' => 'ANY', 'used_quantity' => 1],
        ]);
    }

    public function test_restore_stock_fails_closed_without_garage_context(): void
    {
        $this->expectException(MissingGarageContextException::class);

        app(ComplaintStockService::class)->restoreStock([
            ['code' => 'ANY', 'used_quantity' => 1, 'source_type' => 'warehouse'],
        ]);
    }

    public function test_deduct_stock_fails_closed_in_console_mode_too(): void
    {
        // Unlike HasGarageScope, this guard also fires in console
        // mode — a stock mutation without a garage is always wrong,
        // even from a seeder or artisan command.
        $this->assertTrue(app()->runningInConsole());

        $this->expectException(MissingGarageContextException::class);

        app(ComplaintStockService::class)->deductStock([
            ['code' => 'ANY', 'used_quantity' => 1],
        ]);
    }
}
