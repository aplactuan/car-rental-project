<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Http\Resources\V1\CarResource;
use App\Models\Car;
use App\Models\User;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a car if user is not logged in', function () {
        $car = Car::factory()->create();
        getJson("/api/v1/cars/{$car->id}")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can view a car through api', function () {
        $car = Car::factory()->create();
        getJson("/api/v1/cars/{$car->id}")
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'createdAt',
                'attributes' => [
                    'make', 'model', 'year', 'mileage', 'type', 'numberOfSeats'
                ]
            ]
        ])
        ->assertJsonPath('data.type', 'car')
        ->assertJsonPath('data.id', $car->id)
        ->assertJsonPath('data.attributes.make', $car->make)
        ->assertJsonPath('data.attributes.model', $car->model)
        ->assertJsonPath('data.attributes.year', $car->year)
        ->assertJsonPath('data.attributes.mileage', $car->mileage)
        ->assertJsonPath('data.attributes.vehicleType', $car->type)
        ->assertJsonPath('data.attributes.numberOfSeats', $car->number_of_seats);
    });
});
