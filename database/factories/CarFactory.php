<?php

namespace Database\Factories;

use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Car>
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
            'type' => $this->faker->randomElement(['Sedan', 'SUV', 'Truck', 'Van']),
            'door' => $this->faker->numberBetween(2, 5),
            'seats' => $this->faker->numberBetween(2, 8),
            'year' => (int) $this->faker->year(),
            'color' => $this->faker->safeColorName(),
            'make' => $this->faker->randomElement(['Toyota', 'Honda', 'Ford', 'Chevrolet']),
            'model' => $this->faker->randomElement(['Corolla', 'Civic', 'F-150', 'Camry']),
            'plate_number' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{3}'),
        ];
    }
}
