<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Handles post-login navigation.
 *
 * Priority:
 *   1. Director (company-level)     → director.dashboard
 *   2. 0 active garages             → garage.selection (with error)
 *   3. 1 active garage              → auto-select + dashboard
 *   4. 2+ active garages            → garage.selection
 */
class PostLoginRedirector
{
    public static function redirect(User $user, string $routeName = 'dashboard'): RedirectResponse
    {
        // Directors operate at the company level and have their own dashboard.
        if ($user->isDirector()) {
            return redirect()->route('director.dashboard');
        }

        $activeGarages = $user->garages()
            ->wherePivot('is_active', true)
            ->with('company')
            ->get();

        if ($activeGarages->isEmpty()) {
            return redirect()->route('garage.selection')
                ->with('error', __('messages.flash.no_garage_assigned'));
        }

        if ($activeGarages->count() === 1) {
            self::applyGarageContext($user, $activeGarages->first());

            return redirect()->intended(route($routeName, absolute: false));
        }

        return redirect()->route('garage.selection');
    }

    /**
     * Persist the given garage as the user's current garage.
     */
    public static function applyGarageContext(User $user, $garage): void
    {
        $user->update([
            'current_garage_id'       => $garage->id,
            'current_company_id'      => $garage->company_id,
            'last_selected_garage_at' => now(),
        ]);

        session([
            'current_garage_id'    => $garage->id,
            'current_garage_name'  => $garage->name,
            'current_company_id'   => $garage->company_id,
            'current_company_name' => $garage->company?->name,
        ]);

        GarageContext::set($garage->id, $garage->company_id);
    }
}
