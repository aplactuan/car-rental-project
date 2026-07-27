<?php

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot delete a purchase order when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can delete a purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('purchase_orders', [
            'id' => $purchaseOrder->id,
        ]);
    });
});
