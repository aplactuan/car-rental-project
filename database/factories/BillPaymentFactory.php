<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Bill;
use App\Models\BillPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillPayment>
 */
class BillPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bill_id' => Bill::factory(),
            'amount' => $this->faker->numberBetween(10_000, 1_000_000),
            'method' => $this->faker->randomElement(PaymentMethod::cases())->value,
            'reference_number' => $this->faker->unique()->numerify('REF-########'),
            'notes' => $this->faker->optional()->sentence(),
            'paid_at' => now(),
        ];
    }
}
