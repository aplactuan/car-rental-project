<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function purchaseOrderPayload(array $overrides = []): array
{
    return array_merge([
        'customer_id' => $overrides['customer_id'] ?? Customer::factory()->create()->id,
        'po_number' => 'PO-1001',
        'date' => '2026-07-15',
        'amount' => 250000,
        'request_person' => 'Jane Doe',
        'description' => 'Airport transfer package',
    ], $overrides);
}

describe('guest user', function () {
    test('it cannot add a purchase order if user is not logged in', function () {
        postJson('/api/v1/purchase-orders', purchaseOrderPayload())->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can add a purchase order thru api', function () {
        $customer = Customer::factory()->create();
        $payload = purchaseOrderPayload(['customer_id' => $customer->id]);

        $response = postJson('/api/v1/purchase-orders', $payload);

        assertDatabaseHas('purchase_orders', [
            'customer_id' => $customer->id,
            'po_number' => $payload['po_number'],
            'amount' => $payload['amount'],
            'request_person' => $payload['request_person'],
            'description' => $payload['description'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'createdAt',
                        'poNumber',
                        'date',
                        'amount',
                        'requestPerson',
                        'description',
                        'customerId',
                    ],
                    'relationships' => [
                        'customer',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', 'purchase-order')
            ->assertJsonPath('data.attributes.poNumber', $payload['po_number'])
            ->assertJsonPath('data.attributes.date', $payload['date'])
            ->assertJsonPath('data.attributes.amount', $payload['amount'])
            ->assertJsonPath('data.attributes.requestPerson', $payload['request_person'])
            ->assertJsonPath('data.attributes.description', $payload['description'])
            ->assertJsonPath('data.attributes.customerId', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.id', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $customer->name);
    });

    test('it can add a purchase order without request person and description', function () {
        $customer = Customer::factory()->create();

        $response = postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => $customer->id,
            'request_person' => null,
            'description' => null,
        ]));

        $response->assertStatus(201)
            ->assertJsonPath('data.attributes.requestPerson', null)
            ->assertJsonPath('data.attributes.description', null);
    });

    test('it fails to add a purchase order with a duplicate po number', function () {
        $customer = Customer::factory()->create();
        PurchaseOrder::factory()->forCustomer($customer)->create(['po_number' => 'PO-DUPLICATE']);

        postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => $customer->id,
            'po_number' => 'PO-DUPLICATE',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/po_number');
    });

    test('it fails to add a purchase order with an invalid customer', function () {
        postJson('/api/v1/purchase-orders', purchaseOrderPayload([
            'customer_id' => '00000000-0000-0000-0000-000000000000',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/customer_id');
    });

    test('it fails to add a purchase order with a negative amount', function () {
        postJson('/api/v1/purchase-orders', purchaseOrderPayload(['amount' => -1]))
            ->assertStatus(422)
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/amount');
    });
});
