<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('it cannot view a purchase order if user is not logged in', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}")->assertStatus(401);
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it can view a purchase order through api', function () {
        $customer = Customer::factory()->create(['name' => 'Acme Travel']);
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create([
            'po_number' => 'PO-VIEW-1',
            'date' => '2026-07-20',
            'amount' => 150000,
            'request_person' => 'John Cruz',
            'description' => 'City tour',
        ]);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}")
            ->assertStatus(200)
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
            ->assertJsonPath('data.id', $purchaseOrder->id)
            ->assertJsonPath('data.attributes.poNumber', 'PO-VIEW-1')
            ->assertJsonPath('data.attributes.date', '2026-07-20')
            ->assertJsonPath('data.attributes.amount', 150000)
            ->assertJsonPath('data.attributes.requestPerson', 'John Cruz')
            ->assertJsonPath('data.attributes.description', 'City tour')
            ->assertJsonPath('data.relationships.customer.data.id', $customer->id)
            ->assertJsonPath('data.relationships.customer.data.attributes.name', $customer->name);
    });
});
