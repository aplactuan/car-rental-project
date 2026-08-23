<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ->assertJsonPath('data.0.attributes.status', 'unpaid')
            ->assertJsonPath('data.0.attributes.tripReportCount', 0)
            ->assertJsonPath('data.0.attributes.amount', 0)
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
            ->assertJsonPath('data.attributes.status', 'unpaid')
            ->assertJsonPath('data.attributes.tripReportCount', 0)
            ->assertJsonPath('data.attributes.amount', 0)
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

    test('returns trip report count and amount aggregates on list and show', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = createInvoiceForPurchaseOrder($purchaseOrder, [
            'invoice_number' => 'INV-AGG-001',
        ]);
        $otherInvoice = createInvoiceForPurchaseOrder($purchaseOrder, [
            'invoice_number' => 'INV-AGG-002',
        ]);

        TripReport::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_id' => $invoice->id,
            'amount' => 10000,
        ]);
        TripReport::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_id' => $invoice->id,
            'amount' => 15000,
        ]);
        TripReport::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_id' => $invoice->id,
            'amount' => 20000,
        ]);
        TripReport::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_id' => $otherInvoice->id,
            'amount' => 5000,
        ]);
        TripReport::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_id' => null,
            'amount' => 9999,
        ]);

        $listByNumber = collect(getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data'))
            ->keyBy('attributes.invoiceNumber');

        expect($listByNumber['INV-AGG-001']['attributes']['tripReportCount'])->toBe(3)
            ->and($listByNumber['INV-AGG-001']['attributes']['amount'])->toBe(45000)
            ->and($listByNumber['INV-AGG-002']['attributes']['tripReportCount'])->toBe(1)
            ->and($listByNumber['INV-AGG-002']['attributes']['amount'])->toBe(5000);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.tripReportCount', 3)
            ->assertJsonPath('data.attributes.amount', 45000);
    });

    test('excludes trip reports from another purchase order from invoice aggregates', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $otherPurchaseOrder = PurchaseOrder::factory()->create();
        $invoice = createInvoiceForPurchaseOrder($purchaseOrder);

        TripReport::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
        ]);
        TripReport::factory()->create([
            'purchase_order_id' => $otherPurchaseOrder->id,
            'invoice_id' => $invoice->id,
            'amount' => 9000,
        ]);

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.tripReportCount', 1)
            ->assertJsonPath('data.attributes.amount', 1000);
    });

    test('loads invoice trip report aggregates without n plus one queries', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoices = collect([
            createInvoiceForPurchaseOrder($purchaseOrder),
            createInvoiceForPurchaseOrder($purchaseOrder),
            createInvoiceForPurchaseOrder($purchaseOrder),
        ]);

        foreach ($invoices as $invoice) {
            TripReport::factory()->count(2)->create([
                'purchase_order_id' => $purchaseOrder->id,
                'invoice_id' => $invoice->id,
                'amount' => 1000,
            ]);
        }

        DB::enableQueryLog();

        getJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices")->assertOk();

        expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(3);
    });
});
