<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ->assertJson(CarResource::make($car)->toArray(request()));
    });
});
