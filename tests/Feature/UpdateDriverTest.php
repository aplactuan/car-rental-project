<?php

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

function updateDriverPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'license_number' => 'LIC-'.uniqid(),
        'license_expiry_date' => '2030-01-01',
        'address' => '123 Main St',
        'phone_number' => '+15555550123',
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot update a driver if user is not logged in', function () {
        $driver = Driver::factory()->create();

        putJson("/api/v1/drivers/{$driver->id}", [
            'firstName' => 'John',
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    test('it forbids a regular user from updating a driver', function () {
        $user = User::factory()->create();
        $driver = Driver::factory()->create();

        Sanctum::actingAs($user);

        putJson("/api/v1/drivers/{$driver->id}", updateDriverPayload())
            ->assertForbidden();
    });

    test('it allows an admin user to update a driver through api', function () {
        $admin = User::factory()->admin()->create();
        $driver = Driver::factory()->create();

        Sanctum::actingAs($admin);

        $payload = updateDriverPayload();

        putJson("/api/v1/drivers/{$driver->id}", $payload)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'createdAt',
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

        assertDatabaseHas('drivers', [
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'license_number' => $payload['license_number'],
        ]);
    });

    test('it allows a linked driver user to update their own driver profile', function () {
        $driverUser = User::factory()->create();
        $driver = Driver::factory()->forUser($driverUser)->create();

        Sanctum::actingAs($driverUser);

        putJson("/api/v1/drivers/{$driver->id}", [
            'first_name' => 'Updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.firstName', 'Updated');

        expect($driver->fresh()->first_name)->toBe('Updated');
    });

    test('it forbids a linked driver user from updating another driver profile', function () {
        $driverUser = User::factory()->create();
        Driver::factory()->forUser($driverUser)->create();
        $otherDriver = Driver::factory()->create();

        Sanctum::actingAs($driverUser);

        putJson("/api/v1/drivers/{$otherDriver->id}", [
            'first_name' => 'Updated',
        ])->assertForbidden();
    });
});
