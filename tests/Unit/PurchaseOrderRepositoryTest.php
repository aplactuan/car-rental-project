<?php

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Repositories\Eloquent\PurchaseOrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new PurchaseOrderRepository(new PurchaseOrder);
});

test('create persists purchase order', function () {
    $customer = Customer::factory()->create();
    $data = [
        'customer_id' => $customer->id,
        'po_number' => 'PO-REPO-1',
        'date' => '2026-07-15',
        'amount' => 250000,
        'request_person' => 'Jane Doe',
        'description' => 'Airport transfer',
    ];

    $purchaseOrder = $this->repository->create($data);

    expect($purchaseOrder)->toBeInstanceOf(PurchaseOrder::class)
        ->and($purchaseOrder->po_number)->toBe($data['po_number'])
        ->and($purchaseOrder->amount)->toBe($data['amount'])
        ->and($purchaseOrder->customer_id)->toBe($customer->id)
        ->and($purchaseOrder->relationLoaded('customer'))->toBeTrue()
        ->and($purchaseOrder->customer->is($customer))->toBeTrue();
});

test('find returns purchase order by id with customer', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['po_number' => 'PO-FIND']);

    $found = $this->repository->find($purchaseOrder->id);

    expect($found->id)->toBe($purchaseOrder->id)
        ->and($found->po_number)->toBe('PO-FIND')
        ->and($found->relationLoaded('customer'))->toBeTrue();
});

test('update modifies purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['amount' => 100000]);

    $updated = $this->repository->update($purchaseOrder->id, [
        'amount' => 175000,
        'request_person' => 'Updated Person',
    ]);

    expect($updated->amount)->toBe(175000)
        ->and($updated->request_person)->toBe('Updated Person');
});

test('delete removes purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();

    $this->repository->delete($purchaseOrder->id);

    $this->assertDatabaseMissing('purchase_orders', ['id' => $purchaseOrder->id]);
});

test('paginate returns paginated purchase orders', function () {
    PurchaseOrder::factory()->count(5)->create();

    $result = $this->repository->paginate(2);

    expect($result->count())->toBe(2)
        ->and($result->total())->toBe(5);
});
