<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Repositories\Eloquent\PurchaseOrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
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
        ->and($purchaseOrder->customer->is($customer))->toBeTrue()
        ->and($purchaseOrder->relationLoaded('media'))->toBeTrue()
        ->and($purchaseOrder->getMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION))->toHaveCount(0);
});

test('create persists purchase order with attachments', function () {
    $customer = Customer::factory()->create();

    $purchaseOrder = $this->repository->create([
        'customer_id' => $customer->id,
        'po_number' => 'PO-REPO-ATTACH',
        'date' => '2026-07-15',
        'amount' => 250000,
    ], [
        UploadedFile::fake()->image('a.jpg'),
        UploadedFile::fake()->createWithContent('b.pdf', '%PDF-1.4 fake'),
    ]);

    expect($purchaseOrder->getMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION))->toHaveCount(2);
});

test('update can add and remove attachments', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->addMedia(UploadedFile::fake()->image('remove-me.jpg'))
        ->toMediaCollection(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION);

    $mediaUuid = $purchaseOrder->getFirstMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION)->uuid;

    $updated = $this->repository->update($purchaseOrder->id, [
        'amount' => 175000,
    ], [
        UploadedFile::fake()->image('added.png'),
    ], [$mediaUuid]);

    expect($updated->amount)->toBe(175000)
        ->and($updated->getMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION))->toHaveCount(1)
        ->and($updated->getFirstMedia(PurchaseOrder::ATTACHMENTS_MEDIA_COLLECTION)->file_name)->toBe('added.png');
});

test('find returns purchase order by id with customer', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['po_number' => 'PO-FIND']);

    $found = $this->repository->find($purchaseOrder->id);

    expect($found->id)->toBe($purchaseOrder->id)
        ->and($found->po_number)->toBe('PO-FIND')
        ->and($found->relationLoaded('customer'))->toBeTrue()
        ->and($found->relationLoaded('program'))->toBeTrue();
});

test('create persists purchase order with program', function () {
    $customer = Customer::factory()->create();
    $program = Program::factory()->create();

    $purchaseOrder = $this->repository->create([
        'customer_id' => $customer->id,
        'program_id' => $program->id,
        'po_number' => 'PO-REPO-PROGRAM',
        'date' => '2026-07-15',
        'amount' => 250000,
    ]);

    expect($purchaseOrder->program_id)->toBe($program->id)
        ->and($purchaseOrder->relationLoaded('program'))->toBeTrue()
        ->and($purchaseOrder->program->is($program))->toBeTrue();
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

test('paginate can filter by customer', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    PurchaseOrder::factory()->count(2)->forCustomer($customer)->create();
    PurchaseOrder::factory()->forCustomer($otherCustomer)->create();

    $result = $this->repository->paginate(15, ['customer_id' => $customer->id]);

    expect($result->total())->toBe(2)
        ->and($result->every(fn (PurchaseOrder $purchaseOrder) => $purchaseOrder->customer_id === $customer->id))->toBeTrue();
});

test('paginate can filter by program', function () {
    $program = Program::factory()->create();
    $otherProgram = Program::factory()->create();

    PurchaseOrder::factory()->count(2)->forProgram($program)->create();
    PurchaseOrder::factory()->forProgram($otherProgram)->create();

    $result = $this->repository->paginate(15, ['program_id' => $program->id]);

    expect($result->total())->toBe(2)
        ->and($result->every(fn (PurchaseOrder $purchaseOrder) => $purchaseOrder->program_id === $program->id))->toBeTrue();
});
