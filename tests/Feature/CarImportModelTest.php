<?php

use App\Enums\CarImportStatus;
use App\Models\CarImport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CarImport model', function () {
    test('it can be created with default pending status', function () {
        $carImport = CarImport::factory()->create();

        expect($carImport->status)->toBe(CarImportStatus::Pending)
            ->and($carImport->total_rows)->toBe(0)
            ->and($carImport->imported_count)->toBe(0)
            ->and($carImport->failed_count)->toBe(0)
            ->and($carImport->failures)->toBeNull();
    });

    test('it casts status to CarImportStatus enum', function () {
        $carImport = CarImport::factory()->create(['status' => CarImportStatus::Processing]);

        expect($carImport->fresh()->status)->toBe(CarImportStatus::Processing);
    });

    test('it casts failures to array', function () {
        $failures = [
            ['row' => 2, 'errors' => ['plate_number' => ['The plate number has already been taken.']]],
        ];

        $carImport = CarImport::factory()->create(['failures' => $failures]);

        expect($carImport->fresh()->failures)->toBe($failures);
    });

    test('it uses uuid as primary key', function () {
        $carImport = CarImport::factory()->create();

        expect($carImport->id)->toBeString()
            ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    test('factory processing state sets correct status', function () {
        $carImport = CarImport::factory()->processing()->create();

        expect($carImport->status)->toBe(CarImportStatus::Processing);
    });

    test('factory completed state sets correct status', function () {
        $carImport = CarImport::factory()->completed()->create([
            'imported_count' => 5,
            'failed_count' => 2,
            'total_rows' => 7,
        ]);

        expect($carImport->status)->toBe(CarImportStatus::Completed)
            ->and($carImport->total_rows)->toBe(7)
            ->and($carImport->imported_count)->toBe(5)
            ->and($carImport->failed_count)->toBe(2);
    });

    test('factory failed state sets correct status', function () {
        $carImport = CarImport::factory()->failed()->create();

        expect($carImport->status)->toBe(CarImportStatus::Failed);
    });
});
