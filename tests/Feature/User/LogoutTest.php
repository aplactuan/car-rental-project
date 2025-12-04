<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('guess user cannot logout', function () {
    postJson('/api/logout')->assertStatus(401);
});

test('authenticated user logout and cannot access protected route', function () {
    //create a user
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    postJson('/api/logout')->assertStatus(200);

    $payload = [
        'make' => 'Toyota',
        'model' => 'Raize',
        'year' => 2020,
        'mileage' => 5000,
        'type' => 'SUV',
        'number_of_seats' => 5,
        'plate_number' => 'IJC2912',
    ];

    postJson('/api/v1/cars', $payload)->assertStatus(401);
});
