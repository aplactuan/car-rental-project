<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function driverPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'license_number' => 'LIC-' . uniqid(),
        'license_expiry_date' => '2030-01-01',
        'address' => '123 Main St',
        'phone_number' => '+15555550123',
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot add a driver if user is not login', function () {
        postJson('/api/v1/drivers', driverPayload())->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can add a driver thru api', function () {
        $payload = driverPayload();

        $response = postJson('/api/v1/drivers', $payload);

        assertDatabaseHas('drivers', [
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'license_number' => $payload['license_number'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'createdAt',
                    'attributes' => [
                        'firstName',
                        'lastName',
                        'licenseNumber',
                        'licenseExpiryDate',
                        'address',
                        'phoneNumber',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'driver')
            ->assertJsonPath('data.attributes.firstName', $payload['first_name'])
            ->assertJsonPath('data.attributes.lastName', $payload['last_name'])
            ->assertJsonPath('data.attributes.licenseNumber', $payload['license_number']);
    });
});

