<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'type' => fake()->randomElement([Customer::TYPE_PERSONAL, Customer::TYPE_BUSINESS]),
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => ['type' => Customer::TYPE_PERSONAL]);
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => ['type' => Customer::TYPE_BUSINESS]);
    }
}
