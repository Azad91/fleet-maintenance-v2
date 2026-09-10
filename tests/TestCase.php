<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Tests use the same middleware pipeline as production.
    // Individual tests can manage their own middleware when needed.
}