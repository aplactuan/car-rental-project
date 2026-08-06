<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\Customer;
use App\Models\Program;
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
            'program_id' => null,
            'po_number' => strtoupper($this->faker->unique()->bothify('PO-####-????')),
            'date' => $this->faker->date(),
            'amount' => $this->faker->numberBetween(1000, 500000),
            'request_person' => $this->faker->optional()->name(),
            'description' => $this->faker->optional()->sentence(),
            'status' => PurchaseOrderStatus::Pending,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }

    public function forProgram(Program $program): static
    {
        return $this->state(fn (array $attributes) => [
            'program_id' => $program->id,
        ]);
    }

    public function ok(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Ok,
        ]);
    }
}
