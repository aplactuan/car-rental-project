<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot delete a customer when not authenticated', function () {
        $customer = Customer::factory()->create();

        deleteJson("/api/v1/customers/{$customer->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can delete a customer', function () {
        $customer = Customer::factory()->create();

        deleteJson("/api/v1/customers/{$customer->id}")
            ->assertNoContent();

        assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    });

    test('deleting a customer keeps related purchase orders', function () {
        $customer = Customer::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();

        deleteJson("/api/v1/customers/{$customer->id}")
            ->assertNoContent();

        assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'customer_id' => null,
        ]);
    });

    test('deleting a customer keeps related programs', function () {
        $customer = Customer::factory()->create();
        $program = Program::factory()->forCustomer($customer)->create();

        deleteJson("/api/v1/customers/{$customer->id}")
            ->assertNoContent();

        assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);

        assertDatabaseHas('programs', [
            'id' => $program->id,
            'customer_id' => null,
        ]);
    });
});
