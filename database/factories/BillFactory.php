<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'bill_number' => 'INV-'.fake()->unique()->numerify('########'),
            'amount' => fake()->numberBetween(100_000, 10_000_000),
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'issued_at' => null,
            'due_at' => null,
            'paid_at' => null,
        ];
    }
}
