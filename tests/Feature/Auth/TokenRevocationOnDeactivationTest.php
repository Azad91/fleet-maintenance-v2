<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class TokenRevocationOnDeactivationTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    /**
     * Regression guard for the observer pattern.
     *
     * Before UserObserver existed, deactivating a user via a direct
     * Eloquent call (console command, tinker, future bulk action)
     * left every API token alive. This test fails on that old
     * behavior and passes with the observer in place.
     */
    public function test_direct_model_update_revokes_tokens(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->createToken('device-a');
        $user->createToken('device-b');

        $this->assertEquals(2, $user->tokens()->count());

        $user->update(['is_active' => false]);

        $this->assertEquals(
            0,
            $user->fresh()->tokens()->count(),
            'All API tokens must be revoked on direct model deactivation'
        );
    }

    public function test_reactivating_user_does_not_revoke_tokens(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        // Reactivate
        $user->update(['is_active' => true]);

        // Issue a token AFTER reactivation
        $user->createToken('post-reactivation');

        // No spurious revocation.
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    public function test_name_only_update_does_not_revoke_tokens(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->createToken('device-a');

        $user->update(['name' => 'Renamed User']);

        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    public function test_super_admin_controller_still_revokes_tokens(): void
    {
        $superAdmin = $this->makeSuperAdminWithMfa();

        $target = User::factory()->create(['is_active' => true]);
        $target->createToken('device-a');

        $this->actingAs($superAdmin)
            ->put(route('super-admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'is_active' => 0,
            ]);

        $this->assertEquals(0, $target->fresh()->tokens()->count());
    }
}
