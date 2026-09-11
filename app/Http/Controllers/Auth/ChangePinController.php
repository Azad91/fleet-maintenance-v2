<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_pin'      => ['required', 'string', 'digits_between:4,6'],
            'pin'              => ['required', 'string', 'digits_between:4,6', 'confirmed', 'different:current_pin'],
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
            'pin'            => Hash::make($validated['pin']),
            'pin_is_default' => false,
        ]);

        return PostLoginRedirector::redirect($user);
    }
}
