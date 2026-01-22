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

test('authenticated user can logout', function () {
    //create a user
    $user = User::factory()->create([
        'email' => 'tester@test.com',
        'password' => Hash::make('password1234')
    ]);

    $token = $user->createToken('API token for ' . $user->email);
    $plain = $token->plainTextToken;
    $tokenId = $token->accessToken->id;

    // Logout with the actual token we created
    \Pest\Laravel\withHeader('Authorization', 'Bearer ' . $plain)
        ->postJson('/api/logout')
        ->assertStatus(200);

    // Verify token is deleted from database
    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $tokenId
    ]);

    // Verify the token record is actually gone by checking the count
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('deleted token cannot access protected route', function () {
    // This test verifies that a deleted token cannot be used
    // Note: Due to how Sanctum caches token validation in tests, this test
    // may need to be run separately from the logout test to ensure proper isolation
    
    $user = User::factory()->create([
        'email' => 'tester2@test.com',
        'password' => Hash::make('password1234')
    ]);

    $token = $user->createToken('API token for ' . $user->email);
    $plain = $token->plainTextToken;
    $tokenId = $token->accessToken->id;

    // Delete the token directly (simulating logout)
    $user->tokens()->where('id', $tokenId)->delete();

    // Verify token is deleted
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

    // Try accessing the protected route with deleted token - should fail
    \Pest\Laravel\withHeader('Authorization', 'Bearer ' . $plain)
        ->postJson('/api/v1/cars', $payload)
        ->assertStatus(401);
});
