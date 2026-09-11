<?php

namespace Database\Seeders;

use App\Models\ComplaintType;
use App\Models\Garage;
use Illuminate\Database\Seeder;

class ComplaintTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Engine noise',
            'Tire puncture',
            'Brake problem',
            'Lighting failure',
            'Transmission problem',
            'Suspension problem',
            'Electrical problem',
            'Air conditioning failure',
            'Oil leak',
            'Other',
        ];

        // Seed default complaint types for every existing garage.
        // (Complaint types are now garage-scoped, not global.)
        $garages = Garage::withoutGlobalScopes()->get();

        foreach ($garages as $garage) {
            foreach ($types as $name) {
                ComplaintType::withoutGlobalScopes()->updateOrCreate(
                    [
                        'name'      => $name,
                        'garage_id' => $garage->id,
                    ],
                    [
                        'company_id' => $garage->company_id,
                    ]
                );
            }
        }
    }
}
