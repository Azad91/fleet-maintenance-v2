<?php

namespace Database\Factories;

use App\Models\Bus;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusFactory extends Factory
{
    protected $model = Bus::class;

    public function definition()
    {
        return [
            'garage_id' => Garage::factory(),
            'company_id' => null,
            'bus_project' => $this->faker->word(),
            'dqn' => $this->faker->unique()->regexify('[0-9]{2}-[A-Z]{2}-[0-9]{3}'),
            'route_number' => $this->faker->numerify('#####'),
            'engine_number' => $this->faker->bothify('??-#######'),
            'km' => $this->faker->numberBetween(0, 100000),
            'date' => $this->faker->date(),
            'is_active' => true,
        ];
    }
}
