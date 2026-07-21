<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot get customer children if user is not logged in', function () {
        $customer = Customer::factory()->business()->create();

        getJson("/api/v1/customers/{$customer->id}/children")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it returns the children accounts for a customer', function () {
        $parent = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);
        $firstChild = Customer::factory()->business()->forParent($parent)->create([
            'name' => 'Iligan City Engineers Office',
        ]);
        $secondChild = Customer::factory()->business()->forParent($parent)->create([
            'name' => 'Iligan City Accounting Office',
        ]);
        Customer::factory()->business()->create([
            'name' => 'Unrelated Customer',
        ]);

        getJson("/api/v1/customers/{$parent->id}/children")
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'createdAt',
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
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'customer')
            ->assertJsonPath('data.0.relationships.parent.data.type', 'customer')
            ->assertJsonPath('data.0.relationships.parent.data.id', $parent->id)
            ->assertJsonPath('data.0.relationships.parent.data.attributes.name', $parent->name)
            ->assertJsonPath('data.1.relationships.parent.data.id', $parent->id);

        $childIds = collect(getJson("/api/v1/customers/{$parent->id}/children")->json('data'))
            ->pluck('id')
            ->all();

        expect($childIds)->toContain($firstChild->id, $secondChild->id);
    });

    test('it returns an empty list when the customer has no children', function () {
        $customer = Customer::factory()->business()->create();

        getJson("/api/v1/customers/{$customer->id}/children")
            ->assertSuccessful()
            ->assertExactJson([
                'data' => [],
            ]);
    });

    test('it returns 404 for a non-existent customer', function () {
        getJson('/api/v1/customers/'.fake()->uuid().'/children')
            ->assertNotFound();
    });
});
