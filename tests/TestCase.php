<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bütün testlərdə garage.selected middleware-ni söndür
        $this->withoutMiddleware(\App\Http\Middleware\EnsureGarageSelected::class);
    }
}
