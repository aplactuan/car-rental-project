<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

test('guest cannot change password', function () {
    putJson('/api/password', [
        'current_password' => 'password1234',
        'password' => 'new-password1234',
        'password_confirmation' => 'new-password1234',
    ])->assertUnauthorized();
});

test('authenticated user cannot change password with invalid current password', function () {
    $user = User::factory()->create([
        'password' => 'password1234',
    ]);

    $token = $user->createToken('API token for '.$user->email)->plainTextToken;

    \Pest\Laravel\withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/password', [
            'current_password' => 'invalid-password',
            'password' => 'new-password1234',
            'password_confirmation' => 'new-password1234',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/current_password');
});

test('authenticated user can change password', function () {
    $user = User::factory()->create([
        'password' => 'password1234',
    ]);

    $token = $user->createToken('API token for '.$user->email)->plainTextToken;

    \Pest\Laravel\withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/password', [
            'current_password' => 'password1234',
            'password' => 'new-password1234',
            'password_confirmation' => 'new-password1234',
        ])
        ->assertSuccessful()
        ->assertJsonPath('meta.message', 'Password updated successfully');

    expect(Hash::check('new-password1234', $user->fresh()->password))->toBeTrue();
});

test('password change requires confirmation', function () {
    $user = User::factory()->create([
        'password' => 'password1234',
    ]);

    $token = $user->createToken('API token for '.$user->email)->plainTextToken;

    \Pest\Laravel\withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/password', [
            'current_password' => 'password1234',
            'password' => 'new-password1234',
            'password_confirmation' => 'different-password1234',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/password');
});
