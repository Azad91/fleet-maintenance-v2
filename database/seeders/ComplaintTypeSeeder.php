<?php

namespace Database\Seeders;

use App\Models\ComplaintType;
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

        foreach ($types as $type) {
            ComplaintType::updateOrCreate(['name' => $type]);
        }
    }
}