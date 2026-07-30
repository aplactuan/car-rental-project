<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

function invoiceForDelete(PurchaseOrder $purchaseOrder, array $overrides = []): Invoice
{
    return Invoice::query()->create(array_merge([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-DELETE-001',
        'lddap_adap_no' => 'LDDAP-DELETE',
        'note' => 'Invoice to delete',
    ], $overrides));
}

describe('guest user', function () {
    test('cannot delete an invoice when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDelete($purchaseOrder);

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
    });

    test('can delete an invoice and its media', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDelete($purchaseOrder);
        $invoice->addMedia(UploadedFile::fake()->image('receipt.jpg'))
            ->toMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);
        $invoice->addMedia(UploadedFile::fake()->image('voucher.jpg'))
            ->toMediaCollection(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION);

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertNoContent();

        assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        expect($invoice->getMedia(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION))->toHaveCount(0)
            ->and($invoice->getMedia(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION))->toHaveCount(0);
    });

    test('nullifies related trip report invoice references on delete', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDelete($purchaseOrder);
        $tripReport = TripReport::factory()->for($purchaseOrder)->create([
            'invoice_id' => $invoice->id,
        ]);

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertNoContent();

        assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        assertDatabaseHas('trip_reports', [
            'id' => $tripReport->id,
            'invoice_id' => null,
        ]);
    });

    test('cannot delete an invoice from another purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForDelete(PurchaseOrder::factory()->create());

        deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}")
            ->assertNotFound();

        assertDatabaseHas('invoices', ['id' => $invoice->id]);
    });
});
