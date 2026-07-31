<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\TripReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripReport>
 */
class TripReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'report_date' => $this->faker->date(),
            'trip_start' => $this->faker->date(),
            'trip_end' => $this->faker->date(),
            'driver' => $this->faker->name(),
            'destinations' => $this->faker->city(),
            'amount' => $this->faker->numberBetween(500, 20_000),
        ];
    }
}
