<?php

namespace Database\Factories;

use App\Models\Garage;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class GarageFactory extends Factory
{
    protected $model = Garage::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->city() . ' Qarajı',
            'code' => 'G-' . $this->faker->unique()->numberBetween(100, 999),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'is_active' => true,
        ];
    }
}
