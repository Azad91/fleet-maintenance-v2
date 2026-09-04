<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition()
    {
        return [
            'garage_id' => Garage::factory(),
            'company_id' => null,
            'code' => $this->faker->unique()->bothify('??-###'),
            'name' => $this->faker->word(),
            'quantity' => $this->faker->numberBetween(0, 100),
            'minimum_quantity' => $this->faker->numberBetween(0, 10),
            'price' => $this->faker->numberBetween(10, 1000),
            'unit' => 'ədəd',
        ];
    }
}
