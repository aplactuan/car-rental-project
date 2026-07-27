<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot list purchase orders if user is not logged in', function () {
        getJson('/api/v1/purchase-orders')->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can list purchase orders with default pagination', function () {
        PurchaseOrder::factory()->count(3)->create();

        $response = getJson('/api/v1/purchase-orders');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'createdAt',
                        'attributes' => [
                            'poNumber',
                            'date',
                            'amount',
                            'customerId',
                        ],
                        'relationships' => [
                            'customer',
                        ],
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.type', 'purchase-order');
    });

    test('it respects per_page pagination parameter', function () {
        PurchaseOrder::factory()->count(5)->create();

        $response = getJson('/api/v1/purchase-orders?per_page=2');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    test('it validates per_page parameter', function () {
        $response = getJson('/api/v1/purchase-orders?per_page=0');

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/per_page');
    });

    test('it does not include customer name on list responses', function () {
        $customer = Customer::factory()->create(['name' => 'Acme Travel']);
        PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-LIST-1']);

        $response = getJson('/api/v1/purchase-orders');

        $purchaseOrder = collect($response->json('data'))
            ->firstWhere('attributes.poNumber', 'PO-LIST-1');

        expect($purchaseOrder)->not->toBeNull();
        expect($purchaseOrder['relationships']['customer']['data']['id'])->toBe($customer->id);
        expect($purchaseOrder['relationships']['customer']['data'])->not->toHaveKey('attributes');
    });

    test('it can filter purchase orders by customer', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $matching = PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-MATCH']);
        PurchaseOrder::factory()->forCustomer($otherCustomer)->create(['po_number' => 'PO-OTHER']);

        $response = getJson("/api/v1/purchase-orders?customer_id={$customer->id}");

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.attributes.customerId', $customer->id);
    });

    test('it validates customer_id filter', function () {
        getJson('/api/v1/purchase-orders?customer_id=not-a-uuid')
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });
});
