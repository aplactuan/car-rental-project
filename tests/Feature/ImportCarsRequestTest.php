<?php

use App\Http\Requests\Car\ImportCarsRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::post('/test-import', function (ImportCarsRequest $request) {
        return response()->json(['ok' => true]);
    })->middleware('auth:sanctum');
});

describe('guest user', function () {
    test('it cannot upload a csv if user is not logged in', function () {
        $this->postJson('/test-import')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('it rejects a request with no file', function () {
        $this->postJson('/test-import')
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/file');
    });

    test('it rejects a non-csv file', function () {
        $this->postJson('/test-import', [
            'file' => UploadedFile::fake()->create('cars.pdf', 100, 'application/pdf'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/file');
    });

    test('it rejects a file exceeding 10mb', function () {
        $this->postJson('/test-import', [
            'file' => UploadedFile::fake()->create('cars.csv', 11000, 'text/csv'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/file');
    });

    test('it accepts a valid csv file', function () {
        $this->postJson('/test-import', [
            'file' => UploadedFile::fake()->create('cars.csv', 100, 'text/csv'),
        ])
            ->assertOk();
    });

    test('it accepts a txt file', function () {
        $this->postJson('/test-import', [
            'file' => UploadedFile::fake()->create('cars.txt', 100, 'text/plain'),
        ])
            ->assertOk();
    });
});
