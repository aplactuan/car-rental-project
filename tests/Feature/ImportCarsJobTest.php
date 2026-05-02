<?php

use App\Enums\CarImportStatus;
use App\Jobs\ImportCarsJob;
use App\Models\Car;
use App\Models\CarImport;
use App\Repositories\Contracts\CarRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeCsvFile(array $rows, string $filename = 'cars.csv'): string
{
    Storage::fake('local');

    $headers = ['make', 'model', 'year', 'type', 'number_of_seats', 'mileage', 'plate_number'];
    $lines = [implode(',', $headers)];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(fn ($v) => $v ?? '', $row));
    }

    $path = 'imports/cars/'.$filename;
    Storage::put($path, implode("\n", $lines));

    return $path;
}

describe('ImportCarsJob', function () {
    test('it sets status to processing then completed', function () {
        $path = makeCsvFile([
            ['Toyota', 'Corolla', '2020', 'Sedan', '5', '10000', 'AAA-001'],
        ]);

        $carImport = CarImport::factory()->create(['file_path' => $path]);

        (new ImportCarsJob($carImport))->handle(app(CarRepositoryInterface::class));

        expect($carImport->fresh()->status)->toBe(CarImportStatus::Completed);
    });

    test('it imports all valid rows and updates counts', function () {
        $path = makeCsvFile([
            ['Toyota', 'Corolla', '2020', 'Sedan', '5', '10000', 'AAA-001'],
            ['Honda', 'Civic', '2021', 'Sedan', '5', '8000', 'BBB-002'],
        ]);

        $carImport = CarImport::factory()->create(['file_path' => $path]);

        (new ImportCarsJob($carImport))->handle(app(CarRepositoryInterface::class));

        $carImport->refresh();

        expect($carImport->status)->toBe(CarImportStatus::Completed)
            ->and($carImport->total_rows)->toBe(2)
            ->and($carImport->imported_count)->toBe(2)
            ->and($carImport->failed_count)->toBe(0)
            ->and($carImport->failures)->toBeNull();

        $this->assertDatabaseCount('cars', 2);
    });

    test('it records failures for rows with missing required fields', function () {
        $path = makeCsvFile([
            ['', '', '2020', 'Sedan', '5', '10000', 'AAA-001'],
        ]);

        $carImport = CarImport::factory()->create(['file_path' => $path]);

        (new ImportCarsJob($carImport))->handle(app(CarRepositoryInterface::class));

        $carImport->refresh();

        expect($carImport->imported_count)->toBe(0)
            ->and($carImport->failed_count)->toBe(1)
            ->and($carImport->failures[0]['row'])->toBe(1)
            ->and($carImport->failures[0]['errors'])->toHaveKeys(['make', 'model']);
    });

    test('it records failure for a duplicate plate_number already in the database', function () {
        Car::factory()->create(['plate_number' => 'EXISTING-1']);

        $path = makeCsvFile([
            ['Toyota', 'Corolla', '2020', 'Sedan', '5', '10000', 'EXISTING-1'],
        ]);

        $carImport = CarImport::factory()->create(['file_path' => $path]);

        (new ImportCarsJob($carImport))->handle(app(CarRepositoryInterface::class));

        $carImport->refresh();

        expect($carImport->imported_count)->toBe(0)
            ->and($carImport->failed_count)->toBe(1)
            ->and($carImport->failures[0]['errors'])->toHaveKey('plate_number');
    });

    test('it records failure for a duplicate plate_number within the csv', function () {
        $path = makeCsvFile([
            ['Toyota', 'Corolla', '2020', 'Sedan', '5', '10000', 'DUP-001'],
            ['Honda', 'Civic', '2021', 'Sedan', '5', '8000', 'DUP-001'],
        ]);

        $carImport = CarImport::factory()->create(['file_path' => $path]);

        (new ImportCarsJob($carImport))->handle(app(CarRepositoryInterface::class));

        $carImport->refresh();

        expect($carImport->imported_count)->toBe(1)
            ->and($carImport->failed_count)->toBe(1)
            ->and($carImport->failures[0]['errors'])->toHaveKey('plate_number');
    });

    test('it handles a mix of valid and invalid rows', function () {
        $path = makeCsvFile([
            ['Toyota', 'Corolla', '2020', 'Sedan', '5', '10000', 'MIX-001'],
            ['', 'Civic', '2021', 'Sedan', '5', '8000', 'MIX-002'],
            ['Ford', 'F-150', '2022', 'Truck', '3', '20000', 'MIX-003'],
        ]);

        $carImport = CarImport::factory()->create(['file_path' => $path]);

        (new ImportCarsJob($carImport))->handle(app(CarRepositoryInterface::class));

        $carImport->refresh();

        expect($carImport->total_rows)->toBe(3)
            ->and($carImport->imported_count)->toBe(2)
            ->and($carImport->failed_count)->toBe(1);

        $this->assertDatabaseCount('cars', 2);
    });

    test('it sets status to failed when an exception is thrown', function () {
        $carImport = CarImport::factory()->create(['file_path' => 'imports/cars/nonexistent.csv']);

        $job = new ImportCarsJob($carImport);
        $job->failed(new RuntimeException('File not found'));

        expect($carImport->fresh()->status)->toBe(CarImportStatus::Failed);
    });
});
