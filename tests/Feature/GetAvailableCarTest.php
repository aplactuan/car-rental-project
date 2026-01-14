<?php

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

/**
 * Small helper for consistent car data.
 */
function availableCarPayload(array $overrides = []): array
{
    return array_merge([
        'make' => 'Toyota',
        'model' => 'Corolla',
        'year' => 2020,
        'mileage' => 10000,
        'type' => 'Sedan',
        'number_of_seats' => 5,
        'plate_number' => 'PLATE-' . uniqid(),
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot list available cars if user is not logged in', function () {
        getJson('/api/v1/cars')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can get the list of available cars', function () {
        // Arrange: create some cars
        $car1 = Car::create(availableCarPayload(['plate_number' => 'ABC-1234']));
        $car2 = Car::create(availableCarPayload(['plate_number' => 'XYZ-5678']));

        // Act: call the endpoint
        $response = getJson('/api/v1/cars');

        // Assert: response structure and content
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'createdAt',
                        'attributes' => [
                            'make',
                            'model',
                            'year',
                            'mileage',
                            'type',
                            'numberOfSeats',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'car')
            ->assertJsonPath('data.0.attributes.make', $car1->make)
            ->assertJsonPath('data.0.attributes.model', $car1->model);
    });
});