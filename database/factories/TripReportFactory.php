<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\TripReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TripReport>
 */
class TripReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'report_date' => $this->faker->date(),
            'po_number' => $this->faker->optional()->bothify('PO-####'),
            'time_in' => '08:00',
            'time_out' => '17:00',
            'rate' => $this->faker->numberBetween(500, 2500),
            'odometer_in' => $this->faker->numberBetween(1_000, 50_000),
            'odometer_out' => $this->faker->numberBetween(50_001, 90_000),
            'fuel_liters' => $this->faker->randomFloat(2, 1, 80),
            'fuel_amount' => $this->faker->numberBetween(100, 5_000),
            'invoice_or_or_number' => $this->faker->optional()->bothify('INV-####'),
            'collection_amount' => $this->faker->numberBetween(100, 10_000),
            'percentage' => $this->faker->randomFloat(2, 0, 100),
            'destinations' => [
                ['from' => $this->faker->city(), 'to' => $this->faker->city()],
            ],
            'driver_id_snapshot' => (string) Str::uuid(),
            'driver_name_snapshot' => $this->faker->name(),
            'car_id_snapshot' => (string) Str::uuid(),
            'car_make_snapshot' => $this->faker->randomElement(['Toyota', 'Nissan', 'Honda']),
            'car_model_snapshot' => $this->faker->word(),
            'car_plate_number_snapshot' => strtoupper($this->faker->bothify('???-####')),
            'customer_id_snapshot' => (string) Str::uuid(),
            'customer_name_snapshot' => $this->faker->company(),
            'transaction_name_snapshot' => $this->faker->words(3, true),
        ];
    }
}
