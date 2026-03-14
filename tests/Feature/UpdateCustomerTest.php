<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot update a customer if user is not logged in', function () {
        $customer = Customer::factory()->create();

        putJson("/api/v1/customers/{$customer->id}", [
            'name' => 'Updated Name',
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can update a customer through api', function () {
        $customer = Customer::factory()->create();

        $payload = [
            'name' => 'Updated Corp',
            'type' => Customer::TYPE_BUSINESS,
        ];

        putJson("/api/v1/customers/{$customer->id}", $payload)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'createdAt',
                        'name',
                        'type',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.attributes.name', $payload['name'])
            ->assertJsonPath('data.attributes.type', $payload['type']);

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => $payload['name'],
            'type' => $payload['type'],
        ]);
    });
});
