<?php

use App\Enums\DriverImportStatus;
use App\Jobs\ImportDriversJob;
use App\Models\Driver;
use App\Models\DriverImport;
use App\Models\User;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeDriverCsvFile(array $rows, string $filename = 'drivers.csv'): string
{
    Storage::fake('local');

    $headers = ['first_name', 'last_name', 'license_number', 'license_expiry_date', 'address', 'phone_number', 'email', 'password'];
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
            ['John', 'Doe', 'LIC-001', '2030-01-01', '123 Main St', '555-1234', 'john@example.com', 'password123'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        expect($driverImport->fresh()->status)->toBe(DriverImportStatus::Completed);
    });

    test('it imports all valid rows and updates counts', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'LIC-001', '2030-01-01', '123 Main St', '555-1234', 'john@example.com', 'password123'],
            ['Jane', 'Smith', 'LIC-002', '2031-06-15', '456 Oak Ave', '555-5678', 'jane@example.com', 'password123'],
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
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'name' => 'John Doe']);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'name' => 'Jane Smith']);
    });

    test('it records failures for rows with missing required fields', function () {
        $path = makeDriverCsvFile([
            ['', '', 'LIC-001', '2030-01-01', '123 Main St', '555-1234', 'john@example.com', 'password123'],
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
            ['John', 'Doe', 'EXISTING-1', '2030-01-01', '123 Main St', '555-1234', 'john@example.com', 'password123'],
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
            ['John', 'Doe', 'DUP-001', '2030-01-01', '123 Main St', '555-1234', 'john@example.com', 'password123'],
            ['Jane', 'Smith', 'DUP-001', '2031-06-15', '456 Oak Ave', '555-5678', 'jane@example.com', 'password123'],
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
            ['John', 'Doe', 'MIX-001', '2030-01-01', '123 Main St', '555-1234', 'john@example.com', 'password123'],
            ['', 'Smith', 'MIX-002', '2031-06-15', '456 Oak Ave', '555-5678', 'jane@example.com', 'password123'],
            ['Alice', 'Johnson', 'MIX-003', '2032-03-20', '789 Pine Rd', '555-9012', 'alice@example.com', 'password123'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->total_rows)->toBe(3)
            ->and($driverImport->imported_count)->toBe(2)
            ->and($driverImport->failed_count)->toBe(1);

        $this->assertDatabaseCount('drivers', 2);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'name' => 'John Doe']);
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com', 'name' => 'Alice Johnson']);
    });

    test('it records failure for a duplicate email already in the database', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $path = makeDriverCsvFile([
            ['John', 'Doe', 'EMAIL-001', '2030-01-01', '123 Main St', '555-1234', 'existing@example.com', 'password123'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->imported_count)->toBe(0)
            ->and($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['errors'])->toHaveKey('email');
    });

    test('it records failure for a duplicate email within the csv', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'EMAIL-DUP-1', '2030-01-01', '123 Main St', '555-1234', 'dup@example.com', 'password123'],
            ['Jane', 'Smith', 'EMAIL-DUP-2', '2031-06-15', '456 Oak Ave', '555-5678', 'dup@example.com', 'password123'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->imported_count)->toBe(1)
            ->and($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['errors'])->toHaveKey('email');
    });

    test('it records failures for invalid email and short password', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'INVALID-001', '2030-01-01', '123 Main St', '555-1234', 'not-an-email', 'short'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->imported_count)->toBe(0)
            ->and($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['errors'])->toHaveKeys(['email', 'password']);
    });

    test('it links each imported driver to the created user', function () {
        $path = makeDriverCsvFile([
            ['John', 'Doe', 'LINK-001', '2030-01-01', '123 Main St', '555-1234', 'john@example.com', 'password123'],
        ]);

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $user = User::query()->where('email', 'john@example.com')->first();
        $driver = Driver::query()->where('license_number', 'LINK-001')->first();

        expect($user)->not->toBeNull()
            ->and($driver)->not->toBeNull()
            ->and($driver->user_id)->toBe($user->id);
    });

    test('it records failure for a row with column count mismatch', function () {
        Storage::fake('local');

        $path = 'imports/drivers/mismatch.csv';
        Storage::put($path, "first_name,last_name,license_number,license_expiry_date,address,phone_number,email,password\nJohn,Doe");

        $driverImport = DriverImport::factory()->create(['file_path' => $path]);

        (new ImportDriversJob($driverImport))->handle(app(DriverRepositoryInterface::class));

        $driverImport->refresh();

        expect($driverImport->failed_count)->toBe(1)
            ->and($driverImport->failures[0]['errors']['row'][0])->toBe('Row column count does not match headers.');
    });

    test('it ignores trailing blank csv rows', function () {
        Storage::fake('local');

        $path = 'imports/drivers/trailing-blank-line.csv';
        Storage::put($path, "first_name,last_name,license_number,license_expiry_date,address,phone_number,email,password\nJohn,Doe,LIC-001,2030-01-01,123 Main St,555-1234,john@example.com,password123\n\n");

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
