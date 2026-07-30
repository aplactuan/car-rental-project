<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

function invoiceForDetach(PurchaseOrder $purchaseOrder, array $overrides = []): Invoice
{
    return Invoice::query()->create(array_merge([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-DETACH-001',
        'lddap_adap_no' => 'LDDAP-DETACH',
        'note' => null,
    ], $overrides));
}

describe('guest user', function () {
    test('cannot detach trip reports when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDetach($purchaseOrder);
        $tripReport = TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
        ]);

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports", [
            'trip_report_ids' => [$tripReport->id],
        ])->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Sanctum::actingAs(User::factory()->create());
    });

    test('can bulk detach trip reports from an invoice', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDetach($purchaseOrder);
        $tripReports = TripReport::factory()->for($purchaseOrder)->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);
        $keptTripReport = TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
        ]);

        $response = deleteJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => $tripReports->pluck('id')->all()]
        );

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'trip-report')
            ->assertJsonPath('data.0.relationships.invoice.data', null)
            ->assertJsonPath('data.1.relationships.invoice.data', null)
            ->assertJsonPath('data.2.relationships.invoice.data', null);

        foreach ($tripReports as $tripReport) {
            assertDatabaseHas('trip_reports', [
                'id' => $tripReport->id,
                'invoice_id' => null,
            ]);
        }

        assertDatabaseHas('trip_reports', [
            'id' => $keptTripReport->id,
            'invoice_id' => $invoice->id,
        ]);
    });

    test('cannot detach trip reports that are not attached to the invoice', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDetach($purchaseOrder);
        $otherInvoice = invoiceForDetach($purchaseOrder, ['invoice_number' => 'INV-OTHER']);
        $unattachedTripReport = TripReport::factory()->for($purchaseOrder)->create();
        $otherInvoiceTripReport = TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $otherInvoice->id,
        ]);

        deleteJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => [$unattachedTripReport->id, $otherInvoiceTripReport->id]]
        )->assertUnprocessable();
    });

    test('cannot detach trip reports from an invoice on another purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDetach(PurchaseOrder::factory()->create());
        $tripReport = TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
        ]);

        deleteJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => [$tripReport->id]]
        )->assertNotFound();
    });

    test('validates trip report ids payload', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDetach($purchaseOrder);

        deleteJson(
            "/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}/trip-reports",
            ['trip_report_ids' => []]
        )->assertUnprocessable();
    });
});
