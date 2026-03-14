<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function customerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Acme Corp',
        'type' => Customer::TYPE_BUSINESS,
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot add a customer if user is not logged in', function () {
        postJson('/api/v1/customers', customerPayload())->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can add a customer thru api', function () {
        $payload = customerPayload();

        $response = postJson('/api/v1/customers', $payload);

        assertDatabaseHas('customers', [
            'name' => $payload['name'],
            'type' => $payload['type'],
        ]);

        $response->assertStatus(201)
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
    });

    test('it can add a personal customer', function () {
        $payload = customerPayload(['type' => Customer::TYPE_PERSONAL]);

        $response = postJson('/api/v1/customers', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.type', Customer::TYPE_PERSONAL);
    });
});
