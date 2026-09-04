<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'garage_id' => 1,
            'company_id' => 1,
            'code' => 'W-' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->word(),
            'quantity' => 50,
            'unit' => 'ədəd',
        ];
    }
}
