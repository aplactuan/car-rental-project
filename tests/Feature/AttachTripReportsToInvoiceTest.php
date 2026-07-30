<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function invoiceForAttach(PurchaseOrder $purchaseOrder, array $overrides = []): Invoice
{
    return Invoice::query()->create(array_merge([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-ATTACH-001',
        'lddap_adap_no' => 'LDDAP-ATTACH',
        'note' => null,
    ], $overrides));
}

describe('guest user', function () {
    test('cannot attach trip reports when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForAttach($purchaseOrder);
        $tripReport = TripReport::factory()->for($purchaseOrder)->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports", [
            'trip_report_ids' => [$tripReport->id],
        ])->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('can bulk attach trip reports to an invoice', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForAttach($purchaseOrder);
        $tripReports = TripReport::factory()->for($purchaseOrder)->count(3)->create();

        $response = postJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => $tripReports->pluck('id')->all()]
        );

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'trip-report')
            ->assertJsonPath('data.0.relationships.invoice.data.id', $invoice->id)
            ->assertJsonPath('data.1.relationships.invoice.data.id', $invoice->id)
            ->assertJsonPath('data.2.relationships.invoice.data.id', $invoice->id);

        foreach ($tripReports as $tripReport) {
            assertDatabaseHas('trip_reports', [
                'id' => $tripReport->id,
                'invoice_id' => $invoice->id,
            ]);
        }
    });

    test('can move trip reports from another invoice on the same purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $sourceInvoice = invoiceForAttach($purchaseOrder, ['invoice_number' => 'INV-SOURCE']);
        $targetInvoice = invoiceForAttach($purchaseOrder, ['invoice_number' => 'INV-TARGET']);
        $tripReport = TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $sourceInvoice->id,
        ]);

        postJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$targetInvoice->id}/trip-reports",
            ['trip_report_ids' => [$tripReport->id]]
        )
            ->assertOk()
            ->assertJsonPath('data.0.relationships.invoice.data.id', $targetInvoice->id);

        assertDatabaseHas('trip_reports', [
            'id' => $tripReport->id,
            'invoice_id' => $targetInvoice->id,
        ]);
    });

    test('cannot attach trip reports from another purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForAttach($purchaseOrder);
        $foreignTripReport = TripReport::factory()->for(PurchaseOrder::factory()->create())->create();

        postJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => [$foreignTripReport->id]]
        )->assertUnprocessable();

        assertDatabaseHas('trip_reports', [
            'id' => $foreignTripReport->id,
            'invoice_id' => null,
        ]);
    });

    test('cannot attach trip reports to an invoice from another purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForAttach(PurchaseOrder::factory()->create());
        $tripReport = TripReport::factory()->for($purchaseOrder)->create();

        postJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => [$tripReport->id]]
        )->assertNotFound();
    });

    test('validates trip report ids payload', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForAttach($purchaseOrder);

        postJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => []]
        )->assertUnprocessable();
    });
});
