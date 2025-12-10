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

    $token = $user->createToken('API token for ' . $user->email);
    $plain = $token->plainTextToken;
    $tokenId = $token->accessToken->id;


    \Pest\Laravel\withHeader('Authorization', 'Bearer ' . $plain)
        ->postJson('/api/logout')
        ->assertStatus(200);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenId
    ]);

    $payload = [
        'make' => 'Toyota',
        'model' => 'Raize',
        'year' => 2020,
        'mileage' => 5000,
        'type' => 'SUV',
        'number_of_seats' => 5,
        'plate_number' => 'IJC2912',
    ];



    \Pest\Laravel\withHeader('Authorization', 'Bearer ' . $plain)
        ->postJson('api/v1/cars', $payload)
        ->assertStatus(401);
});
