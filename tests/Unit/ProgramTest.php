<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it belongs to a customer', function () {
    $customer = Customer::factory()->create();
    $program = Program::factory()->forCustomer($customer)->create();

    expect($program->customer)->toBeInstanceOf(Customer::class)
        ->and($program->customer->is($customer))->toBeTrue();
});

test('customer has many programs', function () {
    $customer = Customer::factory()->create();
    Program::factory()->count(2)->forCustomer($customer)->create();

    expect($customer->programs)->toHaveCount(2)
        ->and($customer->programs->first())->toBeInstanceOf(Program::class);
});

test('it has many purchase orders', function () {
    $program = Program::factory()->create();
    PurchaseOrder::factory()->count(2)->forProgram($program)->create();

    expect($program->purchaseOrders)->toHaveCount(2)
        ->and($program->purchaseOrders->first())->toBeInstanceOf(PurchaseOrder::class);
});

test('description can be null', function () {
    $program = Program::factory()->create([
        'description' => null,
    ]);

    expect($program->description)->toBeNull();
});

test('deleting a program keeps purchase orders and nulls program_id', function () {
    $program = Program::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->forProgram($program)->create();

    $program->delete();

    $purchaseOrder->refresh();

    expect($purchaseOrder->exists)->toBeTrue()
        ->and($purchaseOrder->program_id)->toBeNull()
        ->and($purchaseOrder->program)->toBeNull();
});

test('deleting a customer keeps the program and nulls customer_id', function () {
    $customer = Customer::factory()->create();
    $program = Program::factory()->forCustomer($customer)->create();

    $customer->delete();

    $program->refresh();

    expect($program->exists)->toBeTrue()
        ->and($program->customer_id)->toBeNull()
        ->and($program->customer)->toBeNull();
});
