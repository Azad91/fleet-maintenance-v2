<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        return [
            'garage_id' => Garage::factory(),
            'company_id' => null,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'position' => 'mexanik',
            'is_active' => true,
        ];
    }
}
