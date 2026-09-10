<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'garage_id'  => 1,
            'company_id' => 1,
            'first_name' => $this->faker->firstName(),
            'last_name'  => $this->faker->lastName(),
            'position'   => 'master',
            'is_active'  => true,
        ];
    }
}