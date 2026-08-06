<?php

use App\Models\Customer;
use App\Models\Program;
use App\Repositories\Eloquent\ProgramRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new ProgramRepository(new Program);
});

test('create persists program', function () {
    $customer = Customer::factory()->create();
    $data = [
        'customer_id' => $customer->id,
        'name' => 'Tourism Drive',
        'description' => 'Provincial tourism program',
    ];

    $program = $this->repository->create($data);

    expect($program)->toBeInstanceOf(Program::class)
        ->and($program->name)->toBe($data['name'])
        ->and($program->description)->toBe($data['description'])
        ->and($program->customer_id)->toBe($customer->id)
        ->and($program->relationLoaded('customer'))->toBeTrue()
        ->and($program->customer->is($customer))->toBeTrue();
});

test('find returns program by id with customer', function () {
    $program = Program::factory()->create(['name' => 'Test Program']);

    $found = $this->repository->find($program->id);

    expect($found->id)->toBe($program->id)
        ->and($found->name)->toBe('Test Program')
        ->and($found->relationLoaded('customer'))->toBeTrue();
});

test('update modifies program', function () {
    $program = Program::factory()->create(['name' => 'Original']);

    $updated = $this->repository->update($program->id, ['name' => 'Updated']);

    expect($updated->name)->toBe('Updated');
});

test('delete removes program', function () {
    $program = Program::factory()->create();

    $this->repository->delete($program->id);

    $this->assertDatabaseMissing('programs', ['id' => $program->id]);
});

test('paginate returns paginated programs', function () {
    Program::factory()->count(5)->create();

    $result = $this->repository->paginate(2);

    expect($result->count())->toBe(2)
        ->and($result->total())->toBe(5);
});

test('paginate can filter by customer', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    Program::factory()->count(2)->forCustomer($customer)->create();
    Program::factory()->forCustomer($otherCustomer)->create();

    $result = $this->repository->paginate(15, ['customer_id' => $customer->id]);

    expect($result->total())->toBe(2)
        ->and($result->every(fn (Program $program) => $program->customer_id === $customer->id))->toBeTrue();
});
