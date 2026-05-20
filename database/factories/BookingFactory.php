<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+1 month');
        $end = $this->faker->dateTimeBetween($start, '+2 months');

        return [
            'transaction_id' => Transaction::factory(),
            'car_id' => Car::factory(),
            'driver_id' => Driver::factory(),
            'note' => $this->faker->optional(0.7)->sentence(),
            'price' => $this->faker->numberBetween(50, 5000),
            'start_date' => $start,
            'end_date' => $end,
        ];
    }
}
