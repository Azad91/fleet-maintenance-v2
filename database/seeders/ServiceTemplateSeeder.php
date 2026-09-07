<?php

namespace Database\Seeders;

use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use Illuminate\Database\Seeder;

class ServiceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // MotorOilDetail cədvəlindəki bütün unikal km-ləri götür
        $uniqueKms = MotorOilDetail::select('km')->distinct()->orderBy('km')->pluck('km');

        foreach ($uniqueKms as $km) {
            // Hər bir km-ə aid detalları çək
            $details = MotorOilDetail::where('km', $km)->get()->map(function ($item) {
                return [
                    'kodu' => $item->detal_kodu,
                    'adi' => $item->detal_adi,
                    'miqdar' => $item->miqdar,
                    'say' => $item->say,
                ];
            })->toArray();

            // ServiceTemplate yarat
            ServiceTemplate::create([
                'name' => "Motor Yağ Dəyişməsi ({$km} km)",
                'default_km_interval' => $km,
                'details' => $details,
            ]);
        }
    }
}
