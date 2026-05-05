<?php

namespace Database\Factories;

use App\Enums\CarImportStatus;
use App\Models\CarImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarImport>
 */
class CarImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => CarImportStatus::Pending,
            'file_path' => 'imports/cars/'.$this->faker->uuid().'.csv',
            'total_rows' => 0,
            'imported_count' => 0,
            'failed_count' => 0,
            'failures' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(['status' => CarImportStatus::Processing]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => CarImportStatus::Completed,
        ]);
    }

    public function failed(): static
    {
        return $this->state(['status' => CarImportStatus::Failed]);
    }
}
