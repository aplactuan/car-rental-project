<?php

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot list driver names if user is not logged in', function () {
        getJson('/api/v1/drivers/names')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it returns all drivers without pagination with first and last name only', function () {
        Driver::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Anderson',
        ]);
        Driver::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Brown',
        ]);
        Driver::factory()->create([
            'first_name' => 'Carol',
            'last_name' => 'Clark',
        ]);

        $response = getJson('/api/v1/drivers/names');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes' => [
                            'firstName',
                            'lastName',
                        ],
                    ],
                ],
            ])
            ->assertJsonMissingPath('links')
            ->assertJsonMissingPath('meta')
            ->assertJsonMissingPath('data.0.attributes.licenseNumber')
            ->assertJsonMissingPath('data.0.attributes.address')
            ->assertJsonMissingPath('data.0.attributes.phoneNumber')
            ->assertJsonPath('data.0.type', 'driver')
            ->assertJsonPath('data.0.attributes.firstName', 'Alice')
            ->assertJsonPath('data.0.attributes.lastName', 'Anderson')
            ->assertJsonPath('data.1.attributes.firstName', 'Bob')
            ->assertJsonPath('data.1.attributes.lastName', 'Brown')
            ->assertJsonPath('data.2.attributes.firstName', 'Carol')
            ->assertJsonPath('data.2.attributes.lastName', 'Clark');
    });

    test('it returns an empty collection when there are no drivers', function () {
        getJson('/api/v1/drivers/names')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });
});
