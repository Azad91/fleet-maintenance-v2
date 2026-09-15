<?php

namespace Database\Seeders;

use App\Models\Garage;
use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use App\Services\GarageContext;
use Illuminate\Database\Seeder;

class ServiceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $garages = Garage::withoutGlobalScopes()->get();

        foreach ($garages as $garage) {
            GarageContext::set($garage->id, $garage->company_id);

            $this->seedForCurrentGarage($garage);

            GarageContext::clear();
        }
    }

    private function seedForCurrentGarage(Garage $garage): void
    {
        $uniqueKms = MotorOilDetail::query()
            ->select('km')
            ->distinct()
            ->orderBy('km')
            ->pluck('km');

        foreach ($uniqueKms as $km) {
            $details = MotorOilDetail::where('km', $km)->get()->map(function ($item) {
                return [
                    'kodu' => $item->part_code,
                    'adi' => $item->part_name,
                    'miqdar' => $item->quantity,
                    'say' => $item->count,
                ];
            })->toArray();

            ServiceTemplate::create([
                'name' => "Motor Oil Change ({$km} km)",
                'default_km_interval' => $km,
                'details' => $details,
            ]);
        }
    }
}
