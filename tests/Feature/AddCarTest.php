<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot add a car if user is not login', function () {
        $this->withoutExceptionHandling();
        $payload = [
            'make' => 'Toyota',
            'model' => 'Raize',
            'year' => 2020,
            'mileage' => 5000,
            'type' => 'SUV',
            'number_of_seats' => 5,
            'plate_number' => 'IJC2912',
        ];

        $response = $this->post('/api/v1/cars', $payload);

        $response->assertStatus('401');
    });
});

describe('authenticated user', function () {
   beforeEach(function () {
       $this->user = User::factory()->create();

       Sanctum::actingAs($this->user);
   });

    test('it can add a car thru api', function () {
        $this->withoutExceptionHandling();
        $payload = [
            'make' => 'Toyota',
            'model' => 'Raize',
            'year' => 2020,
            'mileage' => 5000,
            'type' => 'SUV',
            'number_of_seats' => 5,
            'plate_number' => 'IJC2912',
        ];

        $response = $this->post('/api/v1/cars', $payload);

        $response->assertStatus(201);

        assertDatabaseHas('cars', [
            'make' => 'Toyota',
            'model' => 'Raize',
        ]);
    });
});

