<?php

namespace Database\Seeders;

use App\Models\MotorOilDetail;
use App\Models\ServiceTemplate;
use Illuminate\Database\Seeder;

class ServiceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Get all unique KM values from MotorOilDetail
        $uniqueKms = MotorOilDetail::select('km')->distinct()->orderBy('km')->pluck('km');

        foreach ($uniqueKms as $km) {
            // Fetch all details for this KM
            $details = MotorOilDetail::where('km', $km)->get()->map(function ($item) {
                return [
                    'kodu'   => $item->part_code,
                    'adi'    => $item->part_name,
                    'miqdar' => $item->quantity,
                    'say'    => $item->count,
                ];
            })->toArray();

            // Create ServiceTemplate
            ServiceTemplate::create([
                'name'                => "Motor Oil Change ({$km} km)",
                'default_km_interval' => $km,
                'details'             => $details,
            ]);
        }
    }
}