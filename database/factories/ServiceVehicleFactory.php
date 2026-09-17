<?php

namespace Database\Factories;

use App\Models\ServiceVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceVehicleFactory extends Factory
{
    protected $model = ServiceVehicle::class;

    public function definition(): array
    {
        return [
            'garage_id'    => 1,
            'company_id'   => 1,
            'name'         => 'Service Vehicle '.$this->faker->unique()->numberBetween(1, 999),
            'plate_number' => strtoupper($this->faker->bothify('##-??-###')),
            'driver_name'  => $this->faker->name(),
            'phone'        => $this->faker->phoneNumber(),
            'is_active'    => true,
            'notes'        => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
