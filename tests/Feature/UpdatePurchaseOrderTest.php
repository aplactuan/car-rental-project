<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot update a purchase order if user is not logged in', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'amount' => 300000,
        ])->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can update a purchase order through api', function () {
        $customer = Customer::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create([
            'po_number' => 'PO-OLD',
            'amount' => 100000,
        ]);

        $payload = [
            'po_number' => 'PO-NEW',
            'date' => '2026-08-01',
            'amount' => 325000,
            'request_person' => 'Updated Person',
            'description' => 'Updated description',
        ];

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", $payload)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'poNumber',
                        'date',
                        'amount',
                        'requestPerson',
                        'description',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'purchase-order')
            ->assertJsonPath('data.attributes.poNumber', $payload['po_number'])
            ->assertJsonPath('data.attributes.date', $payload['date'])
            ->assertJsonPath('data.attributes.amount', $payload['amount'])
            ->assertJsonPath('data.attributes.requestPerson', $payload['request_person'])
            ->assertJsonPath('data.attributes.description', $payload['description']);

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'po_number' => $payload['po_number'],
            'amount' => $payload['amount'],
            'request_person' => $payload['request_person'],
            'description' => $payload['description'],
        ]);
    });

    test('it can reassign a purchase order to another customer', function () {
        $originalCustomer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($originalCustomer)->create();

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'customer_id' => $newCustomer->id,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.customerId', $newCustomer->id)
            ->assertJsonPath('data.relationships.customer.data.id', $newCustomer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $newCustomer->name);

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'customer_id' => $newCustomer->id,
        ]);
    });

    test('it fails to update a purchase order with a duplicate po number', function () {
        $customer = Customer::factory()->create();
        PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-TAKEN']);
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-OWN']);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", ['po_number' => 'PO-TAKEN'])
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/po_number');
    });

    test('it allows keeping the same po number on update', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'po_number' => 'PO-KEEP',
            'amount' => 100000,
        ]);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'po_number' => 'PO-KEEP',
            'amount' => 200000,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.poNumber', 'PO-KEEP')
            ->assertJsonPath('data.attributes.amount', 200000);
    });
});
