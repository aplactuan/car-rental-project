<?php

use App\Enums\CarImportStatus;
use App\Jobs\ImportCarsJob;
use App\Models\CarImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot import cars if not logged in', function () {
        $this->postJson('/api/v1/cars/import')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('local');
        Queue::fake();
        Sanctum::actingAs(User::factory()->create());
    });

    test('it returns 202 and creates a pending car import record', function () {
        $file = UploadedFile::fake()->createWithContent(
            'cars.csv',
            "make,model,year,type,number_of_seats,mileage,plate_number\nToyota,Corolla,2020,Sedan,5,10000,AAA-001"
        );

        $response = $this->postJson('/api/v1/cars/import', ['file' => $file]);

        $response->assertStatus(202)
            ->assertJsonPath('data.type', 'car-import')
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

        $this->assertDatabaseCount('car_imports', 1);
        $this->assertDatabaseHas('car_imports', ['status' => CarImportStatus::Pending->value]);
    });

    test('it stores the csv file and dispatches the job', function () {
        $file = UploadedFile::fake()->createWithContent(
            'cars.csv',
            "make,model,year,type,number_of_seats,mileage,plate_number\nToyota,Corolla,2020,Sedan,5,10000,AAA-001"
        );

        $this->postJson('/api/v1/cars/import', ['file' => $file]);

        $carImport = CarImport::first();

        Storage::assertExists($carImport->file_path);
        Queue::assertPushed(ImportCarsJob::class, fn ($job) => $job->carImport->is($carImport));
    });

    test('it rejects a request with no file', function () {
        $this->postJson('/api/v1/cars/import')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/file');
    });

    test('it rejects a non-csv file', function () {
        $this->postJson('/api/v1/cars/import', [
            'file' => UploadedFile::fake()->create('cars.pdf', 100, 'application/pdf'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/file');
    });
});
