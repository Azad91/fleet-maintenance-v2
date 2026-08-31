<?php

namespace Database\Factories;

use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

class GarageFactory extends Factory
{
    protected $model = Garage::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->bothify('GAR-####'),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'is_active' => true,
        ];
    }
}
