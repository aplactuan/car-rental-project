<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

function createInvoiceForPurchaseOrder(PurchaseOrder $purchaseOrder, array $overrides = []): Invoice
{
    return Invoice::query()->create(array_merge([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-'.fake()->unique()->numerify('####'),
        'lddap_adap_no' => 'LDDAP-'.fake()->numerify('####'),
        'note' => 'Invoice note',
    ], $overrides));
}

describe('guest user', function () {
    test('cannot list or show invoices when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = createInvoiceForPurchaseOrder($purchaseOrder);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices")->assertUnauthorized();
        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('lists only the purchase order invoices', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = createInvoiceForPurchaseOrder($purchaseOrder, [
            'invoice_number' => 'INV-LIST-001',
            'lddap_adap_no' => 'LDDAP-001',
            'note' => 'First invoice',
        ]);
        createInvoiceForPurchaseOrder(PurchaseOrder::factory()->create());

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $invoice->id)
            ->assertJsonPath('data.0.type', 'purchase-order-invoice')
            ->assertJsonPath('data.0.attributes.invoiceNumber', 'INV-LIST-001')
            ->assertJsonPath('data.0.attributes.lddapAdapNo', 'LDDAP-001')
            ->assertJsonPath('data.0.attributes.note', 'First invoice')
            ->assertJsonPath('data.0.relationships.purchaseOrder.data.id', $purchaseOrder->id);
    });

    test('shows an invoice that belongs to the purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = createInvoiceForPurchaseOrder($purchaseOrder, [
            'invoice_number' => 'INV-SHOW-001',
            'lddap_adap_no' => 'LDDAP-SHOW',
            'note' => null,
        ]);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.type', 'purchase-order-invoice')
            ->assertJsonPath('data.attributes.invoiceNumber', 'INV-SHOW-001')
            ->assertJsonPath('data.attributes.lddapAdapNo', 'LDDAP-SHOW')
            ->assertJsonPath('data.attributes.note', null)
            ->assertJsonPath('data.attributes.paymentReceiptUrl', null)
            ->assertJsonPath('data.attributes.disbursementVoucherUrl', null)
            ->assertJsonPath('data.relationships.purchaseOrder.data.id', $purchaseOrder->id);
    });

    test('cannot show an invoice from another purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = createInvoiceForPurchaseOrder(PurchaseOrder::factory()->create());

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertNotFound();
    });
});
