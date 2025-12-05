<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('guess user cannot logout', function () {
    postJson('/api/logout')->assertStatus(401);
});

test('authenticated user logout and cannot access protected route', function () {
    //create a user
    $user = User::factory()->create([
        'email' => 'tester@test.com',
        'password' => Hash::make('password1234')
    ]);

    $response = postJson('/api/login', [
        'email' => 'tester@test.com',
        'password' => 'password1234'
    ]);

    $json_response = $response->json();

    \Pest\Laravel\withHeaders([
        'Authorization' => 'Bearer ' . $json_response['data']['token']
    ])->postJson('/api/logout')->assertStatus(200);



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
