<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot get a customer parent if user is not logged in', function () {
        $parent = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);
        $child = Customer::factory()->business()->forParent($parent)->create([
            'name' => 'Iligan City Engineers Office',
        ]);

        getJson("/api/v1/customers/{$child->id}/parent")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it returns the parent account for a sub-account', function () {
        $parent = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);
        $child = Customer::factory()->business()->forParent($parent)->create([
            'name' => 'Iligan City Engineers Office',
        ]);

        getJson("/api/v1/customers/{$child->id}/parent")
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'createdAt',
                        'name',
                        'type',
                        'parentId',
                    ],
                    'relationships' => [
                        'parent',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.id', $parent->id)
            ->assertJsonPath('data.attributes.name', 'Iligan City Local Government Unit')
            ->assertJsonPath('data.attributes.type', Customer::TYPE_BUSINESS)
            ->assertJsonPath('data.attributes.parentId', null)
            ->assertJsonPath('data.relationships.parent.data', null);
    });

    test('it returns 404 when the customer has no parent', function () {
        $customer = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);

        getJson("/api/v1/customers/{$customer->id}/parent")
            ->assertNotFound()
            ->assertJsonPath('errors.0.detail', 'Customer has no parent account');
    });

    test('it returns 404 for a non-existent customer', function () {
        getJson('/api/v1/customers/'.fake()->uuid().'/parent')
            ->assertNotFound();
    });

    test('it returns the immediate parent for nested sub-accounts', function () {
        $root = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);
        $office = Customer::factory()->business()->forParent($root)->create([
            'name' => 'Iligan City Engineers Office',
        ]);
        $division = Customer::factory()->business()->forParent($office)->create([
            'name' => 'Road Maintenance Division',
        ]);

        getJson("/api/v1/customers/{$division->id}/parent")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $office->id)
            ->assertJsonPath('data.attributes.name', 'Iligan City Engineers Office')
            ->assertJsonPath('data.attributes.parentId', $root->id)
            ->assertJsonPath('data.relationships.parent.data.type', 'customer')
            ->assertJsonPath('data.relationships.parent.data.id', $root->id)
            ->assertJsonPath('data.relationships.parent.data.attributes.name', 'Iligan City Local Government Unit');
    });
});
