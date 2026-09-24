<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

/**
 * User model observer.
 *
 * Revokes every active API token the moment an account transitions
 * from active (is_active = true) to deactivated (is_active = false).
 *
 * WHY THIS EXISTS
 * ---------------
 * SuperAdmin\UserController already revokes tokens inline when it
 * flips the is_active flag, but relying on every caller to remember
 * that step is fragile: a future bulk-update action, a console
 * command, or a data migration could set is_active = false without
 * going through that controller, leaving the API tokens alive.
 *
 * Moving the revocation into a model observer makes the invariant
 * hold for EVERY code path that touches the is_active column
 * through Eloquent — not just the one controller written today.
 */
class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Only act on an is_active change.
        if (! $user->wasChanged('is_active')) {
            return;
        }

        // getOriginal() still returns the pre-update value here —
        // syncOriginal() runs AFTER the updated event is dispatched.
        $wasActive = (bool) $user->getOriginal('is_active');
        $isNowActive = (bool) $user->is_active;

        // Only revoke on the active → inactive transition.
        if (! ($wasActive && ! $isNowActive)) {
            return;
        }

        $revokedCount = $user->tokens()->count();

        if ($revokedCount === 0) {
            return;
        }

        $user->tokens()->delete();

        Log::warning('API tokens revoked after user deactivation', [
            'user_id' => $user->id,
            'revoked_count' => $revokedCount,
            'deactivated_by' => auth()->id(),
            'request_id' => Context::get('request_id'),
        ]);
    }
}
