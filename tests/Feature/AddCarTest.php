<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('it can add a car thru api', function () {
    $payload = [
        'name' => 'Civic',
        'brand' => 'Honda',
        'year' => 2020,
        'price' => 1200000,
    ];

    $response = $this->post('/cars', $payload);


    $response->assertStatus(201);

    assertDatabaseHas('cars', [
        'name' => 'Civic',
        'brand' => 'Honda',
    ]);
});
