<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Car>
 */
class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'make' => $this->faker->randomElement(['Toyota', 'Honda', 'Ford', 'Chevrolet']),
            'model' => $this->faker->randomElement(['Corolla', 'Civic', 'F-150', 'Camry']),
            'year' => $this->faker->year(),
            'type' => $this->faker->randomElement(['Sedan', 'SUV', 'Truck', 'Van']),
            'number_of_seats' => $this->faker->numberBetween(4, 8),
            'mileage' => $this->faker->numberBetween(10000, 100000),
            'plate_number' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{3}')
        ];
    }
}
