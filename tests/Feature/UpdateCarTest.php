<?php

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);


describe('guest user', function () {
    test('it cannot update a car if user is not logged in', function () {
        $car = Car::create(carPayload());

        putJson("/api/v1/cars/{$car->id}", [
            'make' => 'Honda',
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);
    });

    test('it can update a car through api', function () {
        // Arrange: create a car
        $car = Car::create(carPayload([
            'make' => 'Toyota',
            'model' => 'Corolla',
            'type' => 'Sedan',
            'number_of_seats' => 5,
            'plate_number' => 'UPDATE-1',
        ]));

        // Act: update the car
        $updatePayload = [
            'make' => 'Honda',
            'model' => 'Civic',
            'type' => 'Coupe',
            'number_of_seats' => 4,
            'mileage' => 15000,
            'year' => 2021,
        ];

        $response = putJson("/api/v1/cars/{$car->id}", $updatePayload);

        // Assert: database is updated
        assertDatabaseHas('cars', [
            'id' => $car->id,
            'make' => $updatePayload['make'],
            'model' => $updatePayload['model'],
            'type' => $updatePayload['type'],
            'number_of_seats' => $updatePayload['number_of_seats'],
            'mileage' => $updatePayload['mileage'],
            'year' => $updatePayload['year'],
        ]);

        // Assert: response structure and content
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'createdAt',
                    'attributes' => [
                        'make', 'model', 'year', 'mileage', 'type', 'numberOfSeats'
                    ]
                ]
            ])
            ->assertJsonPath('data.type', 'car')
            ->assertJsonPath('data.id', $car->id)
            ->assertJsonPath('data.attributes.make', $updatePayload['make'])
            ->assertJsonPath('data.attributes.model', $updatePayload['model'])
            ->assertJsonPath('data.attributes.year', $updatePayload['year'])
            ->assertJsonPath('data.attributes.mileage', $updatePayload['mileage'])
            ->assertJsonPath('data.attributes.vehicleType', $updatePayload['type'])
            ->assertJsonPath('data.attributes.numberOfSeats', $updatePayload['number_of_seats']);
    });

    test('it can partially update a car (only some fields)', function () {
        // Arrange: create a car
        $car = Car::create(carPayload([
            'make' => 'Toyota',
            'model' => 'Corolla',
            'type' => 'Sedan',
            'number_of_seats' => 5,
            'mileage' => 10000,
            'plate_number' => 'PARTIAL-1',
        ]));

        // Act: update only make and model
        $updatePayload = [
            'make' => 'Honda',
            'model' => 'Accord',
        ];

        $response = putJson("/api/v1/cars/{$car->id}", $updatePayload);

        // Assert: only specified fields are updated, others remain unchanged
        assertDatabaseHas('cars', [
            'id' => $car->id,
            'make' => 'Honda',
            'model' => 'Accord',
            'type' => 'Sedan', // unchanged
            'number_of_seats' => 5, // unchanged
            'mileage' => 10000, // unchanged
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.make', 'Honda')
            ->assertJsonPath('data.attributes.model', 'Accord')
            ->assertJsonPath('data.attributes.vehicleType', 'Sedan')
            ->assertJsonPath('data.attributes.numberOfSeats', 5);
    });

    test('it cannot update a car with duplicate plate_number', function () {
        // Arrange: create two cars
        $car1 = Car::create(carPayload(['plate_number' => 'UNIQUE-1']));
        $car2 = Car::create(carPayload(['plate_number' => 'UNIQUE-2']));

        // Act: try to update car2 with car1's plate_number
        $response = putJson("/api/v1/cars/{$car2->id}", [
            'plate_number' => 'UNIQUE-1',
        ]);

        // Assert: validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plate_number']);
    });

    test('it can update a car with the same plate_number (its own)', function () {
        // Arrange: create a car
        $car = Car::create(carPayload(['plate_number' => 'SAME-PLATE']));

        // Act: update other fields but keep the same plate_number
        $response = putJson("/api/v1/cars/{$car->id}", [
            'make' => 'Honda',
            'plate_number' => 'SAME-PLATE', // same plate number
        ]);

        // Assert: update succeeds
        $response->assertStatus(200);
        assertDatabaseHas('cars', [
            'id' => $car->id,
            'make' => 'Honda',
            'plate_number' => 'SAME-PLATE',
        ]);
    });

    test('it returns 404 when trying to update non-existent car', function () {
        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $response = putJson("/api/v1/cars/{$nonExistentId}", [
            'make' => 'Honda',
        ]);

        $response->assertStatus(404);
    });
});
