<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // ✅ DƏYİŞİKLİK: withoutMiddleware SİLİNDİ
    // İndi testlər production ilə eyni middleware pipeline-ı istifadə edəcək.
    // Hər bir test özü üçün lazım olan middleware-i ayrıca idarə edə bilər.
}
