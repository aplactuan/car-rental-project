<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot list customers if user is not logged in', function () {
        getJson('/api/v1/customers')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can list customers with default pagination', function () {
        Customer::factory()->count(3)->create();

        $response = getJson('/api/v1/customers');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'createdAt',
                        'attributes' => [
                            'name',
                            'type',
                        ],
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.type', 'customer');
    });

    test('it respects per_page pagination parameter', function () {
        Customer::factory()->count(5)->create();

        $response = getJson('/api/v1/customers?per_page=2');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    test('it validates per_page parameter', function () {
        $response = getJson('/api/v1/customers?per_page=0');

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/per_page');
    });
});
