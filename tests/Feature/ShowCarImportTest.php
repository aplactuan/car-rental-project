<?php

use App\Enums\CarImportStatus;
use App\Models\CarImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a car import if not logged in', function () {
        $carImport = CarImport::factory()->create();

        $this->getJson("/api/v1/cars/imports/{$carImport->id}")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('it returns the car import status', function () {
        $carImport = CarImport::factory()->create();

        $this->getJson("/api/v1/cars/imports/{$carImport->id}")
            ->assertOk()
            ->assertJsonPath('data.type', 'car-import')
            ->assertJsonPath('data.id', $carImport->id)
            ->assertJsonPath('data.attributes.status', CarImportStatus::Pending->value)
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
        $carImport = CarImport::factory()->completed()->create([
            'total_rows' => 3,
            'imported_count' => 2,
            'failed_count' => 1,
            'failures' => [['row' => 2, 'errors' => ['make' => ['The make field is required.']]]],
        ]);

        $this->getJson("/api/v1/cars/imports/{$carImport->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.status', CarImportStatus::Completed->value)
            ->assertJsonPath('data.attributes.totalRows', 3)
            ->assertJsonPath('data.attributes.importedCount', 2)
            ->assertJsonPath('data.attributes.failedCount', 1)
            ->assertJsonCount(1, 'data.attributes.failures');
    });

    test('it returns 404 for a non-existent car import', function () {
        $this->getJson('/api/v1/cars/imports/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    });
});
