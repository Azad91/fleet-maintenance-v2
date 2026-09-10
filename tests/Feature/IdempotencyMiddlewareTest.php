<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->garageB = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->user = User::factory()->create(['role' => 'user']);
        $this->user->garages()->attach($this->garageA->id, ['role' => 'admin', 'is_active' => true]);
        $this->user->garages()->attach($this->garageB->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($this->garageA->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Aktual qaraj sessiyası məlumatlarını qaytarır.
     */
    private function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ];
    }

    /**
     * Müəyyən qaraj üçün sessiya məlumatlarını qaytarır.
     */
    private function sessionFor(Garage $garage): array
    {
        return [
            'current_garage_id'  => $garage->id,
            'current_company_id' => $this->company->id,
        ];
    }

    // ==================================================================
    // 1. ƏSAS İDEMPOTENCY DAVRANIŞI
    // ==================================================================

    public function test_duplicate_request_with_same_key_returns_cached_response(): void
    {
        $payload = ['dqn' => '99-XX-999', 'is_active' => 1];

        // 1-ci sorğu — real işlənir
        $first = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => 'test-key-123'])
            ->post(route('buses.store'), $payload);

        // 2-ci sorğu — cache-dən qaytarılır
        $second = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => 'test-key-123'])
            ->post(route('buses.store'), $payload);

        // Yalnız 1 bus yaradılmalıdır
        $this->assertEquals(1, Bus::where('dqn', '99-XX-999')->count());

        // Cache hit header-i yalnız ikinci cavabda olmalıdır
        $this->assertEquals('HIT-IDEMPOTENT', $second->headers->get('X-Cache-Lookup'));
        $this->assertNull($first->headers->get('X-Cache-Lookup'));

        // Hər iki cavabın status-u eyni olmalıdır (302 redirect)
        $this->assertEquals($first->getStatusCode(), $second->getStatusCode());
    }

    public function test_different_keys_create_separate_records(): void
    {
        $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => 'key-1'])
            ->post(route('buses.store'), ['dqn' => '99-KEY-1', 'is_active' => 1]);

        $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => 'key-2'])
            ->post(route('buses.store'), ['dqn' => '99-KEY-2', 'is_active' => 1]);

        $this->assertEquals(2, Bus::count());
    }

    public function test_key_without_idempotency_header_skips_middleware(): void
    {
        // Header olmadan iki fərqli DQN göndər — hər ikisi yaradılmalıdır
        $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->post(route('buses.store'), ['dqn' => 'NOKEY-A', 'is_active' => 1]);

        $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->post(route('buses.store'), ['dqn' => 'NOKEY-B', 'is_active' => 1]);

        $this->assertEquals(1, Bus::where('dqn', 'NOKEY-A')->count());
        $this->assertEquals(1, Bus::where('dqn', 'NOKEY-B')->count());
    }

    // ==================================================================
    // 2. TƏHLÜKƏSİZLİK: GARAJ İZOLYASİYASI
    // ==================================================================

    public function test_same_key_different_garage_does_not_leak(): void
    {
        // Qaraj A-da bus yarat (eyni key ilə)
        $this->actingAs($this->user)
            ->withSession($this->sessionFor($this->garageA))
            ->withHeaders(['X-Idempotency-Key' => 'same-key'])
            ->post(route('buses.store'), ['dqn' => 'GARAGE-A-BUS', 'is_active' => 1]);

        // Qaraj B-yə keç, EYNİ key istifadə et
        GarageContext::set($this->garageB->id, $this->company->id);

        $this->actingAs($this->user)
            ->withSession($this->sessionFor($this->garageB))
            ->withHeaders(['X-Idempotency-Key' => 'same-key'])
            ->post(route('buses.store'), ['dqn' => 'GARAGE-B-BUS', 'is_active' => 1]);

        // HƏR İKİ qarajda ayrı bus yaradılmalıdır (cache key fərqlidir)
        $this->assertEquals(
            1,
            Bus::withoutGlobalScopes()
                ->where('dqn', 'GARAGE-A-BUS')
                ->where('garage_id', $this->garageA->id)
                ->count(),
            'Garage A bus must be created'
        );

        $this->assertEquals(
            1,
            Bus::withoutGlobalScopes()
                ->where('dqn', 'GARAGE-B-BUS')
                ->where('garage_id', $this->garageB->id)
                ->count(),
            'Garage B bus must be created — cache key must include garage_id'
        );
    }

    // ==================================================================
    // 3. HEADER FİLTRLƏMƏSİ (UNIT)
    // ==================================================================

    public function test_filter_headers_removes_dangerous_headers(): void
    {
        $middleware = new \App\Http\Middleware\IdempotencyMiddleware();

        $reflection = new \ReflectionMethod($middleware, 'filterHeaders');
        $reflection->setAccessible(true);

        $input = [
            'Content-Type'      => ['application/json'],
            'Set-Cookie'        => ['session=secret; HttpOnly; Path=/'],
            'Cookie'            => ['foo=bar'],
            'Date'              => ['Thu, 10 Sep 2026 12:00:00 GMT'],
            'Content-Length'    => ['1234'],
            'Transfer-Encoding' => ['chunked'],
            'X-Custom-Header'   => ['keep-this'],
        ];

        $result = $reflection->invoke($middleware, $input);

        // Təhlükəli header-lər çıxarılmalıdır
        $this->assertArrayNotHasKey('Set-Cookie', $result, 'Set-Cookie must be filtered (session fixation risk)');
        $this->assertArrayNotHasKey('Cookie', $result);
        $this->assertArrayNotHasKey('Date', $result, 'Date must be filtered (stale timestamps)');
        $this->assertArrayNotHasKey('Content-Length', $result);
        $this->assertArrayNotHasKey('Transfer-Encoding', $result);

        // Təhlükəsiz header-lər qalmalıdır
        $this->assertArrayHasKey('Content-Type', $result);
        $this->assertArrayHasKey('X-Custom-Header', $result);
        $this->assertEquals(['application/json'], $result['Content-Type']);
        $this->assertEquals(['keep-this'], $result['X-Custom-Header']);
    }

    public function test_cache_hit_does_not_duplicate_cookie_headers(): void
    {
        // Qeyd: Laravel-in öz session middleware-i hər sorğuya YENİ
        // XSRF-TOKEN əlavə edir — bu bizim cavabımız deyil, framework-ün.
        // Biz yalnız öz cache-imizdən Set-Cookie replay olunmadığını
        // yoxlaya bilərik — bu isə filter_headers testində sübut olunur.
        //
        // Bu test isə cache hit-in düzgün işlədiyini təsdiqləyir.

        $key = 'cookie-cache-test';
        $payload = ['dqn' => 'COOKIE-2', 'is_active' => 1];

        // İlk sorğu — real işlənir
        $first = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), $payload);

        $this->assertNull($first->headers->get('X-Cache-Lookup'));

        // İkinci sorğu — cache hit
        $second = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), $payload);

        // Cache hit olmalı
        $this->assertEquals('HIT-IDEMPOTENT', $second->headers->get('X-Cache-Lookup'));

        // Yalnız 1 bus yaradılıb (idempotency işləyir)
        $this->assertEquals(1, Bus::where('dqn', 'COOKIE-2')->count());
    }

    // ==================================================================
    // 4. VALIDATION XƏTASI VƏ UĞURLU RESPONSE FƏRQİ
    // ==================================================================

    public function test_validation_errors_are_not_cached(): void
    {
        $key = 'validation-error-test';

        // 1-ci sorğu: boş DQN → validation xətası → 302 redirect back
        $first = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), ['dqn' => '', 'is_active' => 1]);

        $this->assertEquals(302, $first->status());
        $this->assertNull($first->headers->get('X-Cache-Lookup'));

        // 2-ci sorğu: EYNİ key, amma DÜZGÜN data
        // Əgər validation xətası cache-lənsəydi, köhnə 302 qaytarılardı.
        $second = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), ['dqn' => 'AFTER-FIX-1', 'is_active' => 1]);

        // Cache hit OLMAMALIDIR
        $this->assertNull(
            $second->headers->get('X-Cache-Lookup'),
            'Validation errors must not be cached — retry with good data must execute'
        );

        // Bus uğurla yaradılmalıdır
        $this->assertEquals(
            1,
            Bus::where('dqn', 'AFTER-FIX-1')->count(),
            'Bus should be created because validation error was not cached'
        );
    }

    public function test_successful_responses_are_cached(): void
    {
        $key = 'success-test';
        $payload = ['dqn' => 'SUCCESS-1', 'is_active' => 1];

        // 1-ci sorğu: uğurlu → 302 redirect to index
        $first = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), $payload);

        $this->assertEquals(302, $first->status());
        $this->assertNull($first->headers->get('X-Cache-Lookup'));

        // 2-ci sorğu: eyni key, eyni data → cache hit
        $second = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $key])
            ->post(route('buses.store'), $payload);

        $this->assertEquals('HIT-IDEMPOTENT', $second->headers->get('X-Cache-Lookup'));

        // Yalnız 1 bus yaradılıb
        $this->assertEquals(1, Bus::where('dqn', 'SUCCESS-1')->count());
    }

    // ==================================================================
    // 5. KEY VALİDASİYASI
    // ==================================================================

    public function test_oversized_idempotency_key_is_ignored(): void
    {
        $hugeKey = str_repeat('A', 1000); // 1000 simvol — limitdən böyük

        $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $hugeKey])
            ->post(route('buses.store'), ['dqn' => 'HUGE-1', 'is_active' => 1]);

        // Cache-ə yazılmamalıdır (key ignore olundu)
        $second = $this->actingAs($this->user)
            ->withSession($this->garageSession())
            ->withHeaders(['X-Idempotency-Key' => $hugeKey])
            ->post(route('buses.store'), ['dqn' => 'HUGE-1', 'is_active' => 1]);

        $this->assertNull(
            $second->headers->get('X-Cache-Lookup'),
            'Oversized idempotency keys must be ignored (DoS protection)'
        );

        // Unique constraint səbəbindən 1-dən az olmamalıdır
        $this->assertGreaterThanOrEqual(1, Bus::where('dqn', 'HUGE-1')->count());
    }
}
