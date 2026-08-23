<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a customer if user is not logged in', function () {
        $customer = Customer::factory()->create();
        getJson("/api/v1/customers/{$customer->id}")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can view a customer through api', function () {
        $customer = Customer::factory()->create(['name' => 'Jane Doe', 'type' => Customer::TYPE_PERSONAL]);

        getJson("/api/v1/customers/{$customer->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'type',
                'id',
                'attributes' => [
                    'createdAt',
                    'name',
                    'type',
                    'parentId',
                    'purchaseOrderCount',
                    'purchaseOrderTotal',
                    'unprogrammedPurchaseOrderCount',
                    'unprogrammedPurchaseOrderTotal',
                ],
                'relationships' => [
                    'parent',
                ],
            ]])
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.attributes.name', $customer->name)
            ->assertJsonPath('data.attributes.type', $customer->type)
            ->assertJsonPath('data.attributes.purchaseOrderCount', 0)
            ->assertJsonPath('data.attributes.purchaseOrderTotal', 0)
            ->assertJsonPath('data.attributes.unprogrammedPurchaseOrderCount', 0)
            ->assertJsonPath('data.attributes.unprogrammedPurchaseOrderTotal', 0)
            ->assertJsonPath('data.relationships.parent.data', null);
    });

    test('it includes parent name when viewing a sub-account', function () {
        $parent = Customer::factory()->business()->create([
            'name' => 'Iligan City Local Government Unit',
        ]);
        $child = Customer::factory()->business()->forParent($parent)->create([
            'name' => 'Iligan City Engineers Office',
        ]);

        getJson("/api/v1/customers/{$child->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.relationships.parent.data.id', $parent->id)
            ->assertJsonPath('data.relationships.parent.data.attributes.name', $parent->name);
    });

    test('it returns purchase order rollups for the customer', function () {
        $customer = Customer::factory()->create();
        $program = Program::factory()->forCustomer($customer)->create();

        PurchaseOrder::factory()->forCustomer($customer)->forProgram($program)->create(['amount' => 4000]);
        PurchaseOrder::factory()->forCustomer($customer)->forProgram($program)->create(['amount' => 6000]);
        PurchaseOrder::factory()->forCustomer($customer)->create(['amount' => 1500, 'program_id' => null]);

        getJson("/api/v1/customers/{$customer->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.attributes.purchaseOrderCount', 3)
            ->assertJsonPath('data.attributes.purchaseOrderTotal', 11500)
            ->assertJsonPath('data.attributes.unprogrammedPurchaseOrderCount', 1)
            ->assertJsonPath('data.attributes.unprogrammedPurchaseOrderTotal', 1500);
    });
});
