<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+1 month');
        $end = $this->faker->dateTimeBetween($start, '+2 months');

        return [
            'scheduleable_type' => Car::class,
            'scheduleable_id' => Car::factory(),
            'start_time' => $start,
            'end_time' => $end,
        ];
    }
}
