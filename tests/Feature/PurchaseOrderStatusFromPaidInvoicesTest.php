<?php

use App\Enums\InvoiceStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

function paidCoverageInvoice(PurchaseOrder $purchaseOrder, array $overrides = []): Invoice
{
    return Invoice::query()->create(array_merge([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-STATUS-001',
        'status' => InvoiceStatus::Unpaid,
    ], $overrides));
}

describe('purchase order status from paid invoices', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->admin()->create());
    });

    test('marks purchase order as ok when an invoice is marked paid and covers the amount', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'amount' => 47_800,
            'status' => PurchaseOrderStatus::Pending,
        ]);
        $invoice = paidCoverageInvoice($purchaseOrder);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
            'amount' => 47_800,
        ]);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'status' => 'paid',
        ])->assertOk();

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => PurchaseOrderStatus::Ok->value,
        ]);
    });

    test('marks purchase order as ok when attaching trip reports to a paid invoice covers the amount', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'amount' => 30_000,
            'status' => PurchaseOrderStatus::Pending,
        ]);
        $invoice = paidCoverageInvoice($purchaseOrder, [
            'invoice_number' => 'INV-STATUS-ATTACH',
            'status' => InvoiceStatus::Paid,
        ]);
        $tripReports = TripReport::factory()->for($purchaseOrder)->count(2)->sequence(
            ['amount' => 15_000],
            ['amount' => 15_000],
        )->create();

        postJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => $tripReports->pluck('id')->all()]
        )->assertOk();

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => PurchaseOrderStatus::Ok->value,
        ]);
    });

    test('reverts purchase order to pending when a paid invoice is marked unpaid', function () {
        $purchaseOrder = PurchaseOrder::factory()->ok()->create([
            'amount' => 20_000,
        ]);
        $invoice = paidCoverageInvoice($purchaseOrder, [
            'invoice_number' => 'INV-STATUS-UNPAY',
            'status' => InvoiceStatus::Paid,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
            'amount' => 20_000,
        ]);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'status' => 'unpaid',
        ])->assertOk();

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => PurchaseOrderStatus::Pending->value,
        ]);
    });

    test('reverts purchase order to pending when a covering paid invoice is deleted', function () {
        $purchaseOrder = PurchaseOrder::factory()->ok()->create([
            'amount' => 20_000,
        ]);
        $invoice = paidCoverageInvoice($purchaseOrder, [
            'invoice_number' => 'INV-STATUS-DELETE',
            'status' => InvoiceStatus::Paid,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
            'amount' => 20_000,
        ]);

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertNoContent();

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => PurchaseOrderStatus::Pending->value,
        ]);
    });

    test('marks purchase order as ok when lowering the amount so paid invoices cover it', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'amount' => 50_000,
            'status' => PurchaseOrderStatus::Pending,
        ]);
        $invoice = paidCoverageInvoice($purchaseOrder, [
            'invoice_number' => 'INV-STATUS-AMOUNT',
            'status' => InvoiceStatus::Paid,
        ]);
        TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
            'amount' => 30_000,
        ]);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'amount' => 30_000,
        ])->assertOk()
            ->assertJsonPath('data.attributes.status', PurchaseOrderStatus::Ok->value);

        assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => PurchaseOrderStatus::Ok->value,
            'amount' => 30_000,
        ]);
    });
});
