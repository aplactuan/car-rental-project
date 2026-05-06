<?php

use App\Enums\DriverImportStatus;
use App\Models\DriverImport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DriverImport model', function () {
    test('it can be created with default pending status', function () {
        $driverImport = DriverImport::factory()->create();

        expect($driverImport->status)->toBe(DriverImportStatus::Pending)
            ->and($driverImport->total_rows)->toBe(0)
            ->and($driverImport->imported_count)->toBe(0)
            ->and($driverImport->failed_count)->toBe(0)
            ->and($driverImport->failures)->toBeNull();
    });

    test('it casts status to DriverImportStatus enum', function () {
        $driverImport = DriverImport::factory()->create(['status' => DriverImportStatus::Processing]);

        expect($driverImport->fresh()->status)->toBe(DriverImportStatus::Processing);
    });

    test('it casts failures to array', function () {
        $failures = [
            ['row' => 2, 'errors' => ['license_number' => ['The license number has already been taken.']]],
        ];

        $driverImport = DriverImport::factory()->create(['failures' => $failures]);

        expect($driverImport->fresh()->failures)->toBe($failures);
    });

    test('it uses uuid as primary key', function () {
        $driverImport = DriverImport::factory()->create();

        expect($driverImport->id)->toBeString()
            ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    test('factory processing state sets correct status', function () {
        $driverImport = DriverImport::factory()->processing()->create();

        expect($driverImport->status)->toBe(DriverImportStatus::Processing);
    });

    test('factory completed state sets correct status', function () {
        $driverImport = DriverImport::factory()->completed()->create([
            'imported_count' => 5,
            'failed_count' => 2,
            'total_rows' => 7,
        ]);

        expect($driverImport->status)->toBe(DriverImportStatus::Completed)
            ->and($driverImport->total_rows)->toBe(7)
            ->and($driverImport->imported_count)->toBe(5)
            ->and($driverImport->failed_count)->toBe(2);
    });

    test('factory failed state sets correct status', function () {
        $driverImport = DriverImport::factory()->failed()->create();

        expect($driverImport->status)->toBe(DriverImportStatus::Failed);
    });
});
