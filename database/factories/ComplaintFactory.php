<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\Bus;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition()
    {
        return [
            'bus_id' => Bus::factory(),
            'garage_id' => Garage::factory(),
            'company_id' => null,
            'yer' => 'qaraj',
            'status' => 'gözləmədə',
            'complaint_type' => 'nasazliq',
            'km' => $this->faker->numberBetween(0, 100000),
            'start_date' => now(),
            'start_time' => now()->format('H:i'),
            'end_date' => now(),
            'end_time' => now()->format('H:i'),
        ];
    }
}
