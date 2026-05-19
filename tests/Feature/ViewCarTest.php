<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

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
                        'type', 'door', 'seats', 'year', 'color', 'make', 'model', 'plateNumber',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'car')
            ->assertJsonPath('data.id', $car->id)
            ->assertJsonPath('data.attributes.type', $car->type)
            ->assertJsonPath('data.attributes.door', $car->door)
            ->assertJsonPath('data.attributes.seats', $car->seats)
            ->assertJsonPath('data.attributes.make', $car->make)
            ->assertJsonPath('data.attributes.model', $car->model)
            ->assertJsonPath('data.attributes.year', $car->year)
            ->assertJsonPath('data.attributes.color', $car->color);
    });
});
