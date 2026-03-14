<?php

use App\Models\Customer;
use App\Repositories\Eloquent\CustomerRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new CustomerRepository(new Customer);
});

test('create persists customer', function () {
    $data = [
        'name' => 'Acme Corp',
        'type' => Customer::TYPE_BUSINESS,
    ];

    $customer = $this->repository->create($data);

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->name)->toBe($data['name']);
    expect($customer->type)->toBe($data['type']);
});

test('find returns customer by id', function () {
    $customer = Customer::factory()->create(['name' => 'Test Customer']);

    $found = $this->repository->find($customer->id);

    expect($found->id)->toBe($customer->id);
    expect($found->name)->toBe('Test Customer');
});

test('update modifies customer', function () {
    $customer = Customer::factory()->create(['name' => 'Original']);

    $updated = $this->repository->update($customer->id, ['name' => 'Updated']);

    expect($updated->name)->toBe('Updated');
});

test('delete removes customer', function () {
    $customer = Customer::factory()->create();

    $this->repository->delete($customer->id);

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

test('paginate returns paginated customers', function () {
    Customer::factory()->count(5)->create();

    $result = $this->repository->paginate(2);

    expect($result->count())->toBe(2);
    expect($result->total())->toBe(5);
});
