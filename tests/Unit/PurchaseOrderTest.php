<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;

uses(RefreshDatabase::class);

test('it implements media library for attachments', function () {
    $purchaseOrder = new PurchaseOrder;

    expect($purchaseOrder)->toBeInstanceOf(HasMedia::class)
        ->and(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION)->toBe('attachments');
});

test('it defaults status to pending', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Pending);
});

test('it casts status to purchase order status enum', function () {
    $purchaseOrder = PurchaseOrder::factory()->ok()->create();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Ok);
});

test('it belongs to a customer', function () {
    $customer = Customer::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();

    expect($purchaseOrder->customer)->toBeInstanceOf(Customer::class)
        ->and($purchaseOrder->customer->is($customer))->toBeTrue();
});

test('it belongs to a program', function () {
    $program = Program::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->forProgram($program)->create();

    expect($purchaseOrder->program)->toBeInstanceOf(Program::class)
        ->and($purchaseOrder->program->is($program))->toBeTrue();
});

test('program is optional on purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['program_id' => null]);

    expect($purchaseOrder->program_id)->toBeNull()
        ->and($purchaseOrder->program)->toBeNull();
});

test('customer has many purchase orders', function () {
    $customer = Customer::factory()->create();
    PurchaseOrder::factory()->count(2)->forCustomer($customer)->create();

    expect($customer->purchaseOrders)->toHaveCount(2)
        ->and($customer->purchaseOrders->first())->toBeInstanceOf(PurchaseOrder::class);
});

test('program has many purchase orders', function () {
    $program = Program::factory()->create();
    PurchaseOrder::factory()->count(2)->forProgram($program)->create();

    expect($program->purchaseOrders)->toHaveCount(2)
        ->and($program->purchaseOrders->first())->toBeInstanceOf(PurchaseOrder::class);
});

test('it casts amount as integer and date as date', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'amount' => '125000',
        'date' => '2026-07-15',
    ]);

    expect($purchaseOrder->amount)->toBeInt()->toBe(125000)
        ->and($purchaseOrder->date->toDateString())->toBe('2026-07-15');
});

test('request person and description can be null', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'request_person' => null,
        'description' => null,
    ]);

    expect($purchaseOrder->request_person)->toBeNull()
        ->and($purchaseOrder->description)->toBeNull();
});

test('po number must be unique', function () {
    PurchaseOrder::factory()->create(['po_number' => 'PO-UNIQUE']);

    PurchaseOrder::factory()->create(['po_number' => 'PO-UNIQUE']);
})->throws(QueryException::class);

test('deleting a customer keeps the purchase order and nulls customer_id', function () {
    $customer = Customer::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->forCustomer($customer)->create();

    $customer->delete();

    $purchaseOrder->refresh();

    expect($purchaseOrder->exists)->toBeTrue()
        ->and($purchaseOrder->customer_id)->toBeNull()
        ->and($purchaseOrder->customer)->toBeNull();
});

test('deleting a program keeps the purchase order and nulls program_id', function () {
    $program = Program::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->forProgram($program)->create();

    $program->delete();

    $purchaseOrder->refresh();

    expect($purchaseOrder->exists)->toBeTrue()
        ->and($purchaseOrder->program_id)->toBeNull()
        ->and($purchaseOrder->program)->toBeNull();
});
