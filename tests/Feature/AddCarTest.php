<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function carPayload(array $overrides = []): array
{
    return array_merge([
        'make' => 'Toyota',
        'model' => 'Raize',
        'year' => 2020,
        'mileage' => 5000,
        'type' => 'SUV',
        'number_of_seats' => 5,
        'plate_number' => 'IJC2912',
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot add a car if user is not login', function () {
        postJson('/api/v1/cars', carPayload())->assertStatus(401);
    });
});

describe('authenticated user', function () {
   beforeEach(function () {
       $this->user = User::factory()->create();

       Sanctum::actingAs($this->user);
   });

    test('it can add a car thru api', function () {
        $payload = carPayload();

        $response = postJson('/api/v1/cars', $payload);

        assertDatabaseHas('cars', [
            'make' => $payload['make'],
            'model' => $payload['model'],
        ]);

        $response->assertStatus(201)
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
            ->assertJsonPath('data.attributes.make', $payload['make'])
            ->assertJsonPath('data.attributes.model', $payload['model'])
            ->assertJsonPath('data.attributes.year', $payload['year'])
            ->assertJsonPath('data.attributes.mileage', $payload['mileage'])
            ->assertJsonPath('data.attributes.type', $payload['type'])
            ->assertJsonPath('data.attributes.numberOfSeats', $payload['number_of_seats']);
    });
});

