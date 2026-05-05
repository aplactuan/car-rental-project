<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('add user', function () {
    beforeEach(function () {
        $this->actingUser = User::factory()->create();
    });

    test('requires authentication', function () {
        postJson('/api/v1/users', [])
            ->assertUnauthorized();
    });

    test('returns 403 when user registration is disabled', function () {
        config(['app.allow_user_registration' => false]);

        actingAs($this->actingUser, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertForbidden();
    });

    test('validates required fields', function () {
        config(['app.allow_user_registration' => true]);

        actingAs($this->actingUser, 'sanctum')
            ->postJson('/api/v1/users', [])
            ->assertUnprocessable();
    });

    test('validates unique email', function () {
        config(['app.allow_user_registration' => true]);

        User::factory()->create(['email' => 'existing@example.com']);

        actingAs($this->actingUser, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'John Doe',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertUnprocessable();
    });

    test('validates password confirmation', function () {
        config(['app.allow_user_registration' => true]);

        actingAs($this->actingUser, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'wrongpassword',
            ])
            ->assertUnprocessable();
    });

    test('creates a user successfully', function () {
        config(['app.allow_user_registration' => true]);

        actingAs($this->actingUser, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'createdAt',
                    'attributes' => [
                        'name',
                        'email',
                        'createdAt',
                    ],
                ],
            ])
            ->assertJsonPath('data.attributes.name', 'John Doe')
            ->assertJsonPath('data.attributes.email', 'john@example.com');

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    });
});
