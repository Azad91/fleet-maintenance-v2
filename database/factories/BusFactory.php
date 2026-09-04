<?php

namespace Database\Factories;

use App\Models\Bus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusFactory extends Factory
{
    protected $model = Bus::class;

    public function definition(): array
    {
        return [
            'garage_id' => 1,
            'company_id' => 1,
            'dqn' => $this->faker->unique()->numerify('##-[A-Z]{2}-###'),
            'route_number' => $this->faker->numerify('###'),
            'is_active' => true,
        ];
    }
}
