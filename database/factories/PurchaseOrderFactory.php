<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'po_number' => strtoupper($this->faker->unique()->bothify('PO-####-????')),
            'date' => $this->faker->date(),
            'amount' => $this->faker->numberBetween(1000, 500000),
            'request_person' => $this->faker->optional()->name(),
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }
}
