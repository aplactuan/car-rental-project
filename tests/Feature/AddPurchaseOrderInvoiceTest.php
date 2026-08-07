<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function purchaseOrderInvoicePayload(array $overrides = []): array
{
    return array_merge([
        'invoice_number' => 'INV-1001',
        'lddap_adap_no' => 'LDDAP-2026-001',
        'note' => 'Payment for July trips',
    ], $overrides);
}

describe('guest user', function () {
    test('cannot add an invoice when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", purchaseOrderInvoicePayload())
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
    });

    test('can add an invoice with optional files', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $payload = purchaseOrderInvoicePayload([
            'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'disbursement_voucher' => UploadedFile::fake()->image('voucher.png'),
            'invoice_picture' => UploadedFile::fake()->image('invoice-picture.jpg'),
        ]);

        $response = postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'purchase-order-invoice')
            ->assertJsonPath('data.attributes.invoiceNumber', $payload['invoice_number'])
            ->assertJsonPath('data.attributes.lddapAdapNo', $payload['lddap_adap_no'])
            ->assertJsonPath('data.attributes.note', $payload['note'])
            ->assertJsonPath('data.relationships.purchaseOrder.data.id', $purchaseOrder->id);

        expect($response->json('data.attributes.paymentReceiptUrl'))->not->toBeNull()
            ->and($response->json('data.attributes.disbursementVoucherUrl'))->not->toBeNull()
            ->and($response->json('data.attributes.invoicePictureUrl'))->not->toBeNull();

        assertDatabaseHas('invoices', [
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => $payload['invoice_number'],
            'lddap_adap_no' => $payload['lddap_adap_no'],
            'note' => $payload['note'],
        ]);

        $invoice = Invoice::query()->latest('created_at')->firstOrFail();
        expect($invoice->getFirstMedia(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION))->not->toBeNull()
            ->and($invoice->getFirstMedia(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION))->not->toBeNull()
            ->and($invoice->getFirstMedia(Invoice::INVOICE_PICTURE_MEDIA_COLLECTION))->not->toBeNull();
    });

    test('can add an invoice without an lddap adap no', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", purchaseOrderInvoicePayload([
            'lddap_adap_no' => null,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.attributes.lddapAdapNo', null);

        assertDatabaseHas('invoices', [
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-1001',
            'lddap_adap_no' => null,
        ]);
    });

    test('can add an invoice without files', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", purchaseOrderInvoicePayload([
            'note' => null,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.attributes.paymentReceiptUrl', null)
            ->assertJsonPath('data.attributes.disbursementVoucherUrl', null)
            ->assertJsonPath('data.attributes.invoicePictureUrl', null)
            ->assertJsonPath('data.attributes.note', null)
            ->assertJsonPath('data.attributes.status', 'unpaid');
    });

    test('can add an invoice with a pdf invoice picture', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", purchaseOrderInvoicePayload([
            'invoice_picture' => UploadedFile::fake()->createWithContent('invoice.pdf', '%PDF-1.4 fake'),
        ]))
            ->assertCreated()
            ->assertJsonPath('data.attributes.invoicePictureUrl', fn ($url) => $url !== null);

        $invoice = Invoice::query()->latest('created_at')->firstOrFail();
        expect($invoice->getFirstMedia(Invoice::INVOICE_PICTURE_MEDIA_COLLECTION)->mime_type)
            ->toBe('application/pdf');
    });

    test('rejects invoice picture when file type is invalid', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", purchaseOrderInvoicePayload([
            'invoice_picture' => UploadedFile::fake()->create('invoice.txt', 100, 'text/plain'),
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/invoice_picture');
    });

    test('can add an invoice with paid status', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", purchaseOrderInvoicePayload([
            'status' => 'paid',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.attributes.status', 'paid');

        assertDatabaseHas('invoices', [
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-1001',
            'status' => 'paid',
        ]);
    });

    test('validates invoice attributes', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        Invoice::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'invoice_number' => 'INV-DUPLICATE',
            'lddap_adap_no' => 'LDDAP-EXISTING',
        ]);

        postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices", [
            'invoice_number' => 'INV-DUPLICATE',
            'lddap_adap_no' => '',
            'payment_receipt' => UploadedFile::fake()->create('receipt.txt', 100, 'text/plain'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/invoice_number');
    });
});
