<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // make a factory for Driver model
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'license_number' => strtoupper($this->faker->bothify('??######')),
            'license_expiry_date' => $this->faker->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
            'address' => $this->faker->address(),
            'phone_number' => $this->faker->phoneNumber(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function withUser(): static
    {
        return $this->afterCreating(function (Driver $driver): void {
            if ($driver->user_id !== null) {
                return;
            }

            $user = User::factory()->create([
                'name' => $driver->first_name.' '.$driver->last_name,
            ]);

            $driver->update(['user_id' => $user->id]);
        });
    }
}
