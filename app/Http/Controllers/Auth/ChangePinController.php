<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GarageContext;
use App\Services\PostLoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePinController extends Controller
{
    /**
     * Show the change PIN form.
     */
    public function show(): View
    {
        return view('auth.change-pin');
    }

    /**
     * Update the PIN.
     *
     * This controller is reached from two flows:
     *
     *   1. Forced PIN change after a fresh PIN login (when
     *      `pin_is_default` is still true). In this case the user has
     *      just logged in but has NOT yet selected a garage — the
     *      post-login redirector handles director / auto-select /
     *      multi-garage cases.
     *
     *   2. Voluntary PIN change from the profile page. The user is
     *      already logged in with a garage in context; re-running the
     *      post-login flow would needlessly re-evaluate membership and
     *      could bounce the user away from where they were.
     *
     * We branch on the presence of a resolved garage context rather
     * than on the source of the request, because the redirector is
     * the correct answer only in the first case.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_pin' => ['required', 'string', 'digits_between:4,6'],
            'pin' => ['required', 'string', 'digits_between:4,6', 'confirmed', 'different:current_pin'],
            'pin_confirmation' => ['required', 'string', 'digits_between:4,6'],
        ], [
            'pin.different' => __('messages.pin_change.same_as_current'),
        ]);

        $user = $request->user();

        if (! $user->pin || ! Hash::check($validated['current_pin'], $user->pin)) {
            return back()->withErrors([
                'current_pin' => __('messages.pin_change.incorrect_current'),
            ]);
        }

        $user->update([
            'pin' => Hash::make($validated['pin']),
            'pin_is_default' => false,
        ]);

        // If a garage is already active, the user came from the
        // profile flow — send them straight to the dashboard.
        // Otherwise fall back to the post-login redirector, which
        // handles director / auto-select / multi-garage cases.
        if (GarageContext::resolveGarageId()) {
            return redirect()->intended(route('dashboard'))
                ->with('success', __('messages.pin_change.success'));
        }

        return PostLoginRedirector::redirect($user);
    }
}
