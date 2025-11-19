<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

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
