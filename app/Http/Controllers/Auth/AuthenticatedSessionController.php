<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // ✅ DÜZƏLİŞ: Login olan istifadəçinin əvvəlki qarajını sessiyaya yaz
        $user = Auth::user();

        if ($user && $user->current_garage_id) {
            // Əvvəl qaraj seçilibsə — sessiyaya yaz və dashboard-a yönləndir
            $garage = $user->currentGarage;
            if ($garage) {
                session([
                    'current_garage_id' => $garage->id,
                    'current_garage_name' => $garage->name,
                    'current_company_id' => $garage->company_id,
                    'current_company_name' => $garage->company->name ?? null,
                ]);

                return redirect()->intended(route('dashboard', absolute: false));
            }
        }

        // ❌ Əvvəl qaraj seçilməyibsə — qaraj seçim səhifəsinə yönləndir
        return redirect()->route('garage.selection');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
