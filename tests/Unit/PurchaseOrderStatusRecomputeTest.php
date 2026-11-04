<?php

use App\Enums\InvoiceStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it marks the purchase order as ok when paid invoices cover the amount', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'amount' => 47_800,
        'status' => PurchaseOrderStatus::Pending,
    ]);

    $invoice = Invoice::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-PAID-FULL',
        'status' => InvoiceStatus::Paid,
    ]);

    TripReport::factory()->for($purchaseOrder)->create([
        'invoice_id' => $invoice->id,
        'amount' => 47_800,
    ]);

    $purchaseOrder->recomputeStatus();

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Ok)
        ->and($purchaseOrder->paidAmount())->toBe(47_800);
});

test('it marks the purchase order as ok when paid invoices exceed the amount', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'amount' => 10_000,
        'status' => PurchaseOrderStatus::Pending,
    ]);

    $invoice = Invoice::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-PAID-OVER',
        'status' => InvoiceStatus::Paid,
    ]);

    TripReport::factory()->for($purchaseOrder)->create([
        'invoice_id' => $invoice->id,
        'amount' => 12_500,
    ]);

    $purchaseOrder->recomputeStatus();

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Ok);
});

test('it keeps the purchase order pending when paid invoices are below the amount', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'amount' => 47_800,
        'status' => PurchaseOrderStatus::Pending,
    ]);

    $invoice = Invoice::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-PAID-PARTIAL',
        'status' => InvoiceStatus::Paid,
    ]);

    TripReport::factory()->for($purchaseOrder)->create([
        'invoice_id' => $invoice->id,
        'amount' => 20_000,
    ]);

    $purchaseOrder->recomputeStatus();

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Pending);
});

test('it ignores unpaid invoice amounts when computing paid total', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'amount' => 10_000,
        'status' => PurchaseOrderStatus::Pending,
    ]);

    $paidInvoice = Invoice::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-PAID',
        'status' => InvoiceStatus::Paid,
    ]);

    $unpaidInvoice = Invoice::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-UNPAID',
        'status' => InvoiceStatus::Unpaid,
    ]);

    TripReport::factory()->for($purchaseOrder)->create([
        'invoice_id' => $paidInvoice->id,
        'amount' => 4_000,
    ]);

    TripReport::factory()->for($purchaseOrder)->create([
        'invoice_id' => $unpaidInvoice->id,
        'amount' => 10_000,
    ]);

    $purchaseOrder->recomputeStatus();

    expect($purchaseOrder->paidAmount())->toBe(4_000)
        ->and($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Pending);
});

test('it reverts the purchase order to pending when paid coverage drops below the amount', function () {
    $purchaseOrder = PurchaseOrder::factory()->ok()->create([
        'amount' => 10_000,
    ]);

    $invoice = Invoice::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'invoice_number' => 'INV-WAS-PAID',
        'status' => InvoiceStatus::Unpaid,
    ]);

    TripReport::factory()->for($purchaseOrder)->create([
        'invoice_id' => $invoice->id,
        'amount' => 10_000,
    ]);

    $purchaseOrder->recomputeStatus();

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::Pending);
});
