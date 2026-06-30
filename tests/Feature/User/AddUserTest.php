<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('add user', function () {
    test('is accessible without authentication', function () {
        config(['app.allow_user_registration' => true]);

        postJson('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertCreated();
    });

    test('returns 403 when user registration is disabled', function () {
        config(['app.allow_user_registration' => false]);

        postJson('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertForbidden();
    });

    test('validates required fields', function () {
        config(['app.allow_user_registration' => true]);

        postJson('/api/v1/users', [])
            ->assertUnprocessable();
    });

    test('validates unique email', function () {
        config(['app.allow_user_registration' => true]);

        User::factory()->create(['email' => 'existing@example.com']);

        postJson('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertUnprocessable();
    });

    test('validates password confirmation', function () {
        config(['app.allow_user_registration' => true]);

        postJson('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'wrongpassword',
        ])
            ->assertUnprocessable();
    });

    test('creates a user successfully', function () {
        config(['app.allow_user_registration' => true]);

        postJson('/api/v1/users', [
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
                        'role',
                        'createdAt',
                    ],
                ],
            ])
            ->assertJsonPath('data.attributes.name', 'John Doe')
            ->assertJsonPath('data.attributes.email', 'john@example.com')
            ->assertJsonPath('data.attributes.role', UserRole::User->value);

        expect(User::where('email', 'john@example.com')->first()->role)->toBe(UserRole::User);
    });

    test('defaults role to user when not provided', function () {
        config(['app.allow_user_registration' => true]);

        postJson('/api/v1/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        expect(User::where('email', 'jane@example.com')->first()->role)->toBe(UserRole::User);
    });

    test('rejects admin role on public registration', function () {
        config(['app.allow_user_registration' => true]);

        postJson('/api/v1/users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Admin->value,
        ])->assertUnprocessable();
    });
});
