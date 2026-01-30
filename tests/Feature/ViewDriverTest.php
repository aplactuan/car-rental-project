<?php

namespace Tests\Feature;

use App\Http\Resources\V1\DriverResource;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a driver if user is not logged in', function () {
        $driver = Driver::factory()->create();
        getJson("/api/v1/drivers/{$driver->id}")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can view a driver through api', function () {
        $driver = Driver::factory()->create();
        getJson("/api/v1/drivers/{$driver->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'type',
                'id',
                'attributes' => [
                    'createdAt',
                    'firstName',
                    'lastName',
                    'phoneNumber',
                    'licenseNumber',
                    'licenseExpiryDate',
                    'address',
                ],
            ]])
            ->assertJsonPath('data.type', 'driver')
            ->assertJsonPath('data.id', $driver->id)
            ->assertJsonPath('data.attributes.firstName', $driver->first_name)
            ->assertJsonPath('data.attributes.lastName', $driver->last_name)
            ->assertJsonPath('data.attributes.phoneNumber', $driver->phone_number);
    });
});
