<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Garage;
use Illuminate\Http\Request;

class GarageSelectionController extends Controller
{
public function index()
    {
        $user = auth()->user();

        // 🔥 YALNIZ İSTİFADƏÇİNİN QARAJLARI
        $companies = Company::whereHas('garages', function ($query) use ($user) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                ->where('is_active', true);
            });
        })->with(['garages' => function ($query) use ($user) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                ->where('is_active', true);
            });
        }])->get();

        return view('garage-selection', compact('companies'));
    }

    public function selectGarage(Request $request)
    {
        $request->validate([
            'garage_id' => 'required|exists:garages,id',
        ]);

        // 🔥 YALNIZ İSTİFADƏÇİNİN ÖZ QARAJLARI
        $garage = auth()->user()
            ->garages()
            ->whereKey($request->garage_id)
            ->wherePivot('is_active', true)
            ->with('company')
            ->firstOrFail();

        // Session-a yaz
        session([
            'current_garage_id' => $garage->id,
            'current_garage_name' => $garage->name,
            'current_company_id' => $garage->company_id,
            'current_company_name' => $garage->company->name,
        ]);

        // İstifadəçinin məlumatlarını yenilə
        $user = auth()->user();
        $user->update([
            'current_garage_id' => $garage->id,
            'current_company_id' => $garage->company_id,
            'last_selected_garage_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', "Qaraj seçildi: {$garage->name}");
    }
}
