<?php

use App\Enums\DriverImportStatus;
use App\Jobs\ImportDriversJob;
use App\Models\DriverImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot import drivers if not logged in', function () {
        $this->postJson('/api/v1/drivers/import')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('local');
        Queue::fake();
        Sanctum::actingAs(User::factory()->create());
    });

    test('it returns 202 and creates a pending driver import record', function () {
        $file = UploadedFile::fake()->createWithContent(
            'drivers.csv',
            "first_name,last_name,license_number,license_expiry_date,address,phone_number\nJohn,Doe,LIC-001,2030-01-01,123 Main St,555-1234"
        );

        $response = $this->postJson('/api/v1/drivers/import', ['file' => $file]);

        $response->assertStatus(202)
            ->assertJsonPath('data.type', 'driver-import')
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

        $this->assertDatabaseCount('driver_imports', 1);
        $this->assertDatabaseHas('driver_imports', ['status' => DriverImportStatus::Pending->value]);
    });

    test('it stores the csv file and dispatches the job', function () {
        $file = UploadedFile::fake()->createWithContent(
            'drivers.csv',
            "first_name,last_name,license_number,license_expiry_date,address,phone_number\nJohn,Doe,LIC-001,2030-01-01,123 Main St,555-1234"
        );

        $this->postJson('/api/v1/drivers/import', ['file' => $file]);

        $driverImport = DriverImport::first();

        Storage::assertExists($driverImport->file_path);
        Queue::assertPushed(ImportDriversJob::class, fn ($job) => $job->driverImport->is($driverImport));
    });

    test('it rejects a request with no file', function () {
        $this->postJson('/api/v1/drivers/import')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/file');
    });

    test('it rejects a non-csv file', function () {
        $this->postJson('/api/v1/drivers/import', [
            'file' => UploadedFile::fake()->create('drivers.pdf', 100, 'application/pdf'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/file');
    });
});
