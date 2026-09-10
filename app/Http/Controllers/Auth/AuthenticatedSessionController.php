<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\GarageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user && $user->current_garage_id) {
            $garage = $user->currentGarage;

            if ($garage) {
                session([
                    'current_garage_id'    => $garage->id,
                    'current_garage_name'  => $garage->name,
                    'current_company_id'   => $garage->company_id,
                    'current_company_name' => $garage->company->name ?? null,
                ]);

                return redirect()->intended(route('dashboard', absolute: false));
            }
        }

        return redirect()->route('garage.selection');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        GarageContext::clear();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', __('messages.flash.auth_logged_out'));
    }
}