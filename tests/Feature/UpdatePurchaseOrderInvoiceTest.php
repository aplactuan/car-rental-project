<?php

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

function invoiceForUpdate(PurchaseOrder $purchaseOrder, array $overrides = []): Invoice
{
    return Invoice::query()->create(array_merge([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-UPDATE-001',
        'lddap_adap_no' => 'LDDAP-ORIGINAL',
        'note' => 'Original note',
    ], $overrides));
}

describe('guest user', function () {
    test('cannot update an invoice when not authenticated', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForUpdate($purchaseOrder);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'note' => 'Updated note',
        ])->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
    });

    test('can update invoice attributes and replace its files', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForUpdate($purchaseOrder);
        $invoice->addMedia(UploadedFile::fake()->image('old-receipt.jpg'))
            ->toMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);
        $invoice->addMedia(UploadedFile::fake()->image('old-voucher.jpg'))
            ->toMediaCollection(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION);

        $response = putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'invoice_number' => 'INV-UPDATED-002',
            'lddap_adap_no' => 'LDDAP-UPDATED',
            'note' => 'Updated note',
            'status' => 'paid',
            'payment_receipt' => UploadedFile::fake()->image('new-receipt.png'),
            'disbursement_voucher' => UploadedFile::fake()->image('new-voucher.webp'),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonPath('data.attributes.invoiceNumber', 'INV-UPDATED-002')
            ->assertJsonPath('data.attributes.lddapAdapNo', 'LDDAP-UPDATED')
            ->assertJsonPath('data.attributes.note', 'Updated note')
            ->assertJsonPath('data.attributes.status', 'paid');

        expect($response->json('data.attributes.paymentReceiptUrl'))->not->toBeNull()
            ->and($response->json('data.attributes.disbursementVoucherUrl'))->not->toBeNull();

        assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'invoice_number' => 'INV-UPDATED-002',
            'lddap_adap_no' => 'LDDAP-UPDATED',
            'note' => 'Updated note',
            'status' => 'paid',
        ]);

        $invoice->refresh();

        expect($invoice->getMedia(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION))->toHaveCount(1)
            ->and($invoice->getFirstMedia(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION)?->file_name)->toBe('new-receipt.png')
            ->and($invoice->getMedia(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION))->toHaveCount(1)
            ->and($invoice->getFirstMedia(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION)?->file_name)->toBe('new-voucher.webp');
    });

    test('can partially update an invoice without removing existing files', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForUpdate($purchaseOrder);
        $invoice->addMedia(UploadedFile::fake()->image('receipt.jpg'))
            ->toMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'note' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.invoiceNumber', 'INV-UPDATE-001')
            ->assertJsonPath('data.attributes.note', null);

        expect($invoice->fresh()->getFirstMedia(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION)?->file_name)
            ->toBe('receipt.jpg');
    });

    test('can remove payment receipt and disbursement voucher', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForUpdate($purchaseOrder);
        $invoice->addMedia(UploadedFile::fake()->image('receipt.jpg'))
            ->toMediaCollection(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION);
        $invoice->addMedia(UploadedFile::fake()->image('voucher.jpg'))
            ->toMediaCollection(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'remove_payment_receipt' => true,
            'remove_disbursement_voucher' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.paymentReceiptUrl', null)
            ->assertJsonPath('data.attributes.disbursementVoucherUrl', null);

        $invoice->refresh();

        expect($invoice->getFirstMedia(Invoice::PAYMENT_RECEIPT_MEDIA_COLLECTION))->toBeNull()
            ->and($invoice->getFirstMedia(Invoice::DISBURSEMENT_VOUCHER_MEDIA_COLLECTION))->toBeNull();
    });

    test('cannot upload a file while also requesting its removal', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForUpdate($purchaseOrder);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'remove_payment_receipt' => true,
            'payment_receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertUnprocessable();
    });

    test('cannot update an invoice from another purchase order', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForUpdate(PurchaseOrder::factory()->create());

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'note' => 'Updated note',
        ])->assertNotFound();
    });

    test('validates updated invoice attributes and files', function () {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $invoice = invoiceForUpdate($purchaseOrder);
        invoiceForUpdate($purchaseOrder, ['invoice_number' => 'INV-TAKEN']);

        putJson("/api/v1/purchase-orders/{$purchaseOrder->id}/invoices/{$invoice->id}", [
            'invoice_number' => 'INV-TAKEN',
            'lddap_adap_no' => '',
            'status' => 'pending',
            'payment_receipt' => UploadedFile::fake()->create('receipt.txt', 100, 'text/plain'),
        ])->assertUnprocessable();
    });
});
