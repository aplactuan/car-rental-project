<?php

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

function driverListPayload(array $overrides = []): array
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
    test('it cannot list drivers if user is not logged in', function () {
        getJson('/api/v1/drivers')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can list drivers with default pagination', function () {
        Driver::factory()->count(3)->create();

        $response = getJson('/api/v1/drivers');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.type', 'driver');
    });

    test('it respects per_page pagination parameter', function () {
        Driver::factory()->count(5)->create();

        $response = getJson('/api/v1/drivers?per_page=2');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    test('it validates per_page parameter', function () {
        $response = getJson('/api/v1/drivers?per_page=0');

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/per_page');
    });
});

