<?php

namespace Database\Factories;

use App\Enums\DriverImportStatus;
use App\Models\DriverImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverImport>
 */
class DriverImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => DriverImportStatus::Pending,
            'file_path' => 'imports/drivers/'.$this->faker->uuid().'.csv',
            'total_rows' => 0,
            'imported_count' => 0,
            'failed_count' => 0,
            'failures' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(['status' => DriverImportStatus::Processing]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => DriverImportStatus::Completed,
        ]);
    }

    public function failed(): static
    {
        return $this->state(['status' => DriverImportStatus::Failed]);
    }
}
