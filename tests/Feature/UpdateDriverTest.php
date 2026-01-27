<?php


use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\putJson;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

function updateDriverPayload(array $overrides = []): array
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
    test('it cannot update a driver if user is not logged in', function () {
        $driver = Driver::factory()->create();

        putJson("/api/v1/drivers/{$driver->id}", [
            'firstName' => 'John',
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);
    });

    test('it can update a driver through api', function () {
        $driver = Driver::factory()->create();

        $payload = updateDriverPayload();


        putJson("/api/v1/drivers/{$driver->id}", $payload)
            ->assertStatus(200)
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

        assertDatabaseHas('drivers', [
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'license_number' => $payload['license_number'],
        ]);
    });
});
