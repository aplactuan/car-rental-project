<?php

use App\Enums\DriverImportStatus;
use App\Models\DriverImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a driver import if not logged in', function () {
        $driverImport = DriverImport::factory()->create();

        $this->getJson("/api/v1/drivers/imports/{$driverImport->id}")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('it returns the driver import status', function () {
        $driverImport = DriverImport::factory()->create();

        $this->getJson("/api/v1/drivers/imports/{$driverImport->id}")
            ->assertOk()
            ->assertJsonPath('data.type', 'driver-import')
            ->assertJsonPath('data.id', $driverImport->id)
            ->assertJsonPath('data.attributes.status', DriverImportStatus::Pending->value)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'status', 'totalRows', 'importedCount', 'failedCount', 'failures', 'createdAt', 'updatedAt',
                    ],
                ],
            ]);
    });

    test('it reflects the completed status with counts', function () {
        $driverImport = DriverImport::factory()->completed()->create([
            'total_rows' => 3,
            'imported_count' => 2,
            'failed_count' => 1,
            'failures' => [['row' => 2, 'errors' => ['first_name' => ['The first name field is required.']]]],
        ]);

        $this->getJson("/api/v1/drivers/imports/{$driverImport->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.status', DriverImportStatus::Completed->value)
            ->assertJsonPath('data.attributes.totalRows', 3)
            ->assertJsonPath('data.attributes.importedCount', 2)
            ->assertJsonPath('data.attributes.failedCount', 1)
            ->assertJsonCount(1, 'data.attributes.failures');
    });

    test('it returns 404 for a non-existent driver import', function () {
        $this->getJson('/api/v1/drivers/imports/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    });
});
