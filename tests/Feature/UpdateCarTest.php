<?php

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
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
            'door' => 4,
            'seats' => 5,
            'color' => 'White',
            'plate_number' => 'UPDATE-1',
        ]));

        // Act: update the car
        $updatePayload = [
            'make' => 'Honda',
            'model' => 'Civic',
            'type' => 'Coupe',
            'door' => 2,
            'seats' => 4,
            'year' => 2021,
            'color' => 'Black',
        ];

        $response = putJson("/api/v1/cars/{$car->id}", $updatePayload);

        // Assert: database is updated
        assertDatabaseHas('cars', [
            'id' => $car->id,
            'make' => $updatePayload['make'],
            'model' => $updatePayload['model'],
            'type' => $updatePayload['type'],
            'door' => $updatePayload['door'],
            'seats' => $updatePayload['seats'],
            'year' => $updatePayload['year'],
            'color' => $updatePayload['color'],
        ]);

        // Assert: response structure and content
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'createdAt',
                    'attributes' => [
                        'type', 'door', 'seats', 'year', 'color', 'make', 'model', 'plateNumber',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'car')
            ->assertJsonPath('data.id', $car->id)
            ->assertJsonPath('data.attributes.type', $updatePayload['type'])
            ->assertJsonPath('data.attributes.door', $updatePayload['door'])
            ->assertJsonPath('data.attributes.seats', $updatePayload['seats'])
            ->assertJsonPath('data.attributes.make', $updatePayload['make'])
            ->assertJsonPath('data.attributes.model', $updatePayload['model'])
            ->assertJsonPath('data.attributes.year', $updatePayload['year'])
            ->assertJsonPath('data.attributes.color', $updatePayload['color']);
    });

    test('it can partially update a car (only some fields)', function () {
        // Arrange: create a car
        $car = Car::create(carPayload([
            'make' => 'Toyota',
            'model' => 'Corolla',
            'type' => 'Sedan',
            'door' => 4,
            'seats' => 5,
            'color' => 'Blue',
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
            'door' => 4, // unchanged
            'seats' => 5, // unchanged
            'color' => 'Blue', // unchanged
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.make', 'Honda')
            ->assertJsonPath('data.attributes.model', 'Accord')
            ->assertJsonPath('data.attributes.type', 'Sedan')
            ->assertJsonPath('data.attributes.seats', 5);
    });

    test('it cannot update a car with duplicate plate_number', function () {
        // Arrange: create two cars
        $car1 = Car::create(carPayload(['plate_number' => 'UNIQUE-1']));
        $car2 = Car::create(carPayload(['plate_number' => 'UNIQUE-2']));

        // Act: try to update car2 with car1's plate_number
        $response = putJson("/api/v1/cars/{$car2->id}", [
            'plate_number' => 'UNIQUE-1',
        ]);

        // Assert: validation error (JSON:API format uses source.pointer)
        $response->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/plate_number');
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
