<?php

use App\Enums\DriverImportStatus;
use App\Jobs\ImportDriversJob;
use App\Models\Driver;
use App\Models\DriverImport;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeDriverCsvFile(array $rows, string $filename = 'drivers.csv'): string
{
    Storage::fake('local');

    $headers = ['first_name', 'last_name', 'license_number', 'license_expiry_date', 'address', 'phone_number'];
    $lines = [implode(',', $headers)];

    foreach ($rows as $row) {
        $lines[] = implode(',', array_map(fn ($v) => $v ?? '', $row));
    }

    $path = 'imports/drivers/'.$filename;
    Storage::put($path, implode("\n", $lines));

    return $path;
}

describe('ImportDriversJob', function () {
    test('it sets status to processing then completed', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'LIC-001', '2030-01-01', '123 Main St', '555-1234'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        expect($driverImport->fresh()->status)->toBe(DriverImportStatus::Completed);
    });

    test('it imports all valid rows and updates counts', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'LIC-001', '2030-01-01', '123 Main St', '555-1234'],
            ['Jane', 'Smith', 'LIC-002', '2031-06-15', '456 Oak Ave', '555-5678'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->status)->toBe(DriverImportStatus::Completed)
            ->and($driverImport->total_rows)->toBe(2)
            ->and($driverImport->imported_count)->toBe(2)
            ->and($driverImport->failed_count)->toBe(0)
            ->and($driverImport->failures)->toBeNull();

        $this->assertDatabaseCount('drivers', 2);
    });

    test('it records failures for rows with missing required fields', function () {
        $path = makeDriverCsvFile([
            ['', '', 'LIC-001', '2030-01-01', '123 Main St', '555-1234'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->imported_count)->toBe(0)
            ->and($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['row'])->toBe(1)
            ->and($driverImport->failures[0]['errors'])->toHaveKeys(['first_name', 'last_name']);
    });

    test('it records failure for a duplicate license_number already in the database', function () {
        Driver::factory()->create(['license_number' => 'EXISTING-1']);

        $path = makeDriverCsvFile([
            ['John', 'Doe', 'EXISTING-1', '2030-01-01', '123 Main St', '555-1234'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->imported_count)->toBe(0)
            ->and($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['errors'])->toHaveKey('license_number');
    });

    test('it records failure for a duplicate license_number within the csv', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'DUP-001', '2030-01-01', '123 Main St', '555-1234'],
            ['Jane', 'Smith', 'DUP-001', '2031-06-15', '456 Oak Ave', '555-5678'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->imported_count)->toBe(1)
            ->and($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['errors'])->toHaveKey('license_number');
    });

    test('it handles a mix of valid and invalid rows', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'MIX-001', '2030-01-01', '123 Main St', '555-1234'],
            ['', 'Smith', 'MIX-002', '2031-06-15', '456 Oak Ave', '555-5678'],
            ['Alice', 'Johnson', 'MIX-003', '2032-03-20', '789 Pine Rd', '555-9012'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->total_rows)->toBe(3)
            ->and($driverImport->imported_count)->toBe(2)
            ->and($driverImport->failed_count)->toBe(1);

        $this->assertDatabaseCount('drivers', 2);
    });

    test('it records failure for a row with column count mismatch', function () {
        Storage::fake('local');

        $path = 'imports/drivers/mismatch.csv';
        Storage::put($path, "first_name,last_name,license_number,license_expiry_date,address,phone_number\nJohn,Doe");

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['errors']['row'][0])->toBe('Row column count does not match headers.');
    });

    test('it ignores trailing blank csv rows', function () {
        Storage::fake('local');

        $path = 'imports/drivers/trailing-blank-line.csv';
        Storage::put($path, "first_name,last_name,license_number,license_expiry_date,address,phone_number\nJohn,Doe,LIC-001,2030-01-01,123 Main St,555-1234\n\n");

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->status)->toBe(DriverImportStatus::Completed)
            ->and($driverImport->total_rows)->toBe(1)
            ->and($driverImport->imported_count)->toBe(1)
            ->and($driverImport->failed_count)->toBe(0)
            ->and($driverImport->failures)->toBeNull();
    });

    test('it sets status to failed when an exception is thrown', function () {
        $driverImport = DriverImport::factory()->create(['file_path' => 'imports/drivers/nonexistent.csv']);

        $job = new ImportDriversJob($driverImport);
        $job->failed(new RuntimeException('File not found'));

        expect($driverImport->fresh()->status)->toBe(DriverImportStatus::Failed);
    });
});
