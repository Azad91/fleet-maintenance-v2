<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\TwoFactor\TwoFactorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorInactiveUserTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdminWithMfa(): User
    {
        $manager = app(TwoFactorManager::class);

        $sa = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $sa->forceFill([
            'two_factor_secret' => $manager->generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ])->save();

        return $sa->fresh();
    }

    public function test_inactive_user_is_blocked_at_two_factor_challenge(): void
    {
        $sa = $this->makeSuperAdminWithMfa();

        // Simulate: password step succeeded, then admin deactivated.
        $sa->update(['is_active' => false]);

        $response = $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => '123456']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', __('auth.inactive'));
        $this->assertGuest();
    }

    public function test_inactive_user_session_is_cleared_on_block(): void
    {
        $sa = $this->makeSuperAdminWithMfa();
        $sa->update(['is_active' => false]);

        $this->withSession(['two_factor.user_id' => $sa->id])
            ->post(route('two-factor.challenge.store'), ['code' => '123456']);

        $this->assertNull(session('two_factor.user_id'));
    }
}
