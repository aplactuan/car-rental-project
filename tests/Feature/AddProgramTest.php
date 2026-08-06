<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function programPayload(array $overrides = []): array
{
    return array_merge([
        'customer_id' => $overrides['customer_id'] ?? Customer::factory()->create()->id,
        'name' => 'Tourism Drive 2026',
        'description' => 'Provincial tourism transport program',
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot add a program if user is not logged in', function () {
        postJson('/api/v1/programs', programPayload())->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can add a program thru api', function () {
        $customer = Customer::factory()->create();
        $payload = programPayload(['customer_id' => $customer->id]);

        $response = postJson('/api/v1/programs', $payload);

        assertDatabaseHas('programs', [
            'customer_id' => $customer->id,
            'name' => $payload['name'],
            'description' => $payload['description'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'createdAt',
                        'name',
                        'description',
                        'customerId',
                    ],
                    'relationships' => [
                        'customer',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'program')
            ->assertJsonPath('data.attributes.name', $payload['name'])
            ->assertJsonPath('data.attributes.description', $payload['description'])
            ->assertJsonPath('data.attributes.customerId', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.id', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $customer->name);
    });

    test('it can add a program without description', function () {
        $customer = Customer::factory()->create();
        $payload = programPayload([
            'customer_id' => $customer->id,
            'description' => null,
        ]);

        postJson('/api/v1/programs', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.attributes.description', null);
    });

    test('it fails to add a program without a name', function () {
        postJson('/api/v1/programs', programPayload(['name' => null]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/name');
    });

    test('it fails to add a program without a customer', function () {
        postJson('/api/v1/programs', [
            'name' => 'Tourism Drive 2026',
            'description' => 'Provincial tourism transport program',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });

    test('it fails to add a program with an invalid customer', function () {
        postJson('/api/v1/programs', programPayload([
            'customer_id' => '00000000-0000-0000-0000-000000000000',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });
});
