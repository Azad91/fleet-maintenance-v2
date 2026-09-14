<?php

namespace Tests\Traits;

use App\Models\User;
use App\Support\TwoFactor\TwoFactorManager;

/**
 * Helper for tests that need a fully set-up SuperAdmin.
 *
 * The `2fa.verified` middleware redirects any SuperAdmin without
 * confirmed MFA to the setup wizard. Tests that are NOT about MFA
 * itself (onboarding, pivot audit, form requests, etc.) need a
 * ready SuperAdmin so the rest of the flow runs normally.
 */
trait MakesSuperAdminWithMfa
{
    protected function makeSuperAdminWithMfa(array $attributes = []): User
    {
        $manager = app(TwoFactorManager::class);

        $superAdmin = User::factory()->create(array_merge([
            'role' => 'super_admin',
            'is_active' => true,
        ], $attributes));

        $superAdmin->forceFill([
            'two_factor_secret' => $manager->generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ])->save();

        return $superAdmin->fresh();
    }
}
