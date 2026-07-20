<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot add a sub-account if user is not logged in', function () {
        $parent = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);

        postJson('/api/v1/customers', [
            'name' => 'Iligan City Engineers Office',
            'type' => Customer::TYPE_BUSINESS,
            'parent_id' => $parent->id,
        ])->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can create a sub-account under a parent customer', function () {
        $parent = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);

        $payload = [
            'name' => 'Iligan City Engineers Office',
            'type' => Customer::TYPE_BUSINESS,
            'parent_id' => $parent->id,
        ];

        $response = postJson('/api/v1/customers', $payload);

        assertDatabaseHas('customers', [
            'name' => $payload['name'],
            'type' => $payload['type'],
            'parent_id' => $parent->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.attributes.name', $payload['name'])
            ->assertJsonPath('data.attributes.type', $payload['type'])
            ->assertJsonPath('data.attributes.parentId', $parent->id)
            ->assertJsonPath('data.relationships.parent.data.type', 'customer')
            ->assertJsonPath('data.relationships.parent.data.id', $parent->id)
            ->assertJsonPath('data.relationships.parent.data.attributes.name', $parent->name);

        $subAccount = Customer::query()->where('name', $payload['name'])->first();

        expect($subAccount->isSubAccount())->toBeTrue();
        expect($subAccount->parent->is($parent))->toBeTrue();
        expect($parent->children)->toHaveCount(1);
        expect($parent->children->first()->is($subAccount))->toBeTrue();
    });

    test('it can create a top-level customer without a parent', function () {
        $response = postJson('/api/v1/customers', [
            'name' => 'Iligan City Local Government Unit',
            'type' => Customer::TYPE_BUSINESS,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.parentId', null)
            ->assertJsonPath('data.relationships.parent.data', null);

        assertDatabaseHas('customers', [
            'name' => 'Iligan City Local Government Unit',
            'parent_id' => null,
        ]);
    });

    test('it rejects a sub-account with a non-existent parent', function () {
        postJson('/api/v1/customers', [
            'name' => 'Iligan City Engineers Office',
            'type' => Customer::TYPE_BUSINESS,
            'parent_id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/parent_id')
            ->assertJsonPath('errors.0.detail', 'The selected parent account does not exist.');
    });

    test('it can assign a parent when updating a customer', function () {
        $parent = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);
        $customer = Customer::factory()->business()->create([
            'name' => 'Iligan City Engineers Office',
        ]);

        putJson("/api/v1/customers/{$customer->id}", [
            'parent_id' => $parent->id,
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.parentId', $parent->id)
            ->assertJsonPath('data.relationships.parent.data.id', $parent->id)
            ->assertJsonPath('data.relationships.parent.data.attributes.name', $parent->name);

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'parent_id' => $parent->id,
        ]);
    });

    test('it can clear a parent when updating a customer', function () {
        $parent = Customer::factory()->business()->create();
        $customer = Customer::factory()->business()->forParent($parent)->create();

        putJson("/api/v1/customers/{$customer->id}", [
            'parent_id' => null,
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.parentId', null)
            ->assertJsonPath('data.relationships.parent.data', null);

        assertDatabaseHas('customers', [
            'id' => $customer->id,
            'parent_id' => null,
        ]);
    });

    test('it cannot set a customer as its own parent', function () {
        $customer = Customer::factory()->create();

        putJson("/api/v1/customers/{$customer->id}", [
            'parent_id' => $customer->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/parent_id')
            ->assertJsonPath('errors.0.detail', 'A customer cannot be its own parent.');
    });

    test('it cannot set a descendant as a parent', function () {
        $parent = Customer::factory()->business()->create();
        $child = Customer::factory()->business()->forParent($parent)->create();

        putJson("/api/v1/customers/{$parent->id}", [
            'parent_id' => $child->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/parent_id')
            ->assertJsonPath('errors.0.detail', 'A customer cannot have one of its sub-accounts as a parent.');
    });

    test('deleting a parent nullifies the sub-account parent_id', function () {
        $parent = Customer::factory()->business()->create();
        $child = Customer::factory()->business()->forParent($parent)->create();

        $parent->delete();

        expect($child->fresh()->parent_id)->toBeNull();
    });
});
