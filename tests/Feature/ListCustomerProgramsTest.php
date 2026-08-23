<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot list customer programs when not authenticated', function () {
        $customer = Customer::factory()->create();

        getJson("/api/v1/customers/{$customer->id}/programs")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('it returns programs for the customer', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $matching = Program::factory()->forCustomer($customer)->create(['name' => 'Customer Program']);
        Program::factory()->forCustomer($otherCustomer)->create(['name' => 'Other Program']);

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'program')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.attributes.name', 'Customer Program')
            ->assertJsonPath('data.0.attributes.customerId', $customer->id)
            ->assertJsonPath('data.0.attributes.purchaseOrderCount', 0)
            ->assertJsonPath('data.0.attributes.purchaseOrderTotal', 0)
            ->assertJsonPath('data.0.relationships.customer.data.id', $customer->id)
            ->assertJsonPath('meta.unprogrammedPurchaseOrderCount', 0)
            ->assertJsonPath('meta.unprogrammedPurchaseOrderTotal', 0)
            ->assertJsonMissingPath('links');
    });

    test('it returns all programs for the customer without pagination', function () {
        $customer = Customer::factory()->create();
        Program::factory()->count(5)->forCustomer($customer)->create();

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.unprogrammedPurchaseOrderCount', 0)
            ->assertJsonMissingPath('links');
    });

    test('it returns an empty list when the customer has no programs', function () {
        $customer = Customer::factory()->create();

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.unprogrammedPurchaseOrderCount', 0)
            ->assertJsonPath('meta.unprogrammedPurchaseOrderTotal', 0);
    });

    test('it returns not found for a missing customer', function () {
        getJson('/api/v1/customers/'.fake()->uuid().'/programs')
            ->assertNotFound();
    });

    test('it returns purchase order aggregates per program', function () {
        $customer = Customer::factory()->create();
        $program = Program::factory()->forCustomer($customer)->create();

        PurchaseOrder::factory()->forCustomer($customer)->forProgram($program)->create(['amount' => 1000]);
        PurchaseOrder::factory()->forCustomer($customer)->forProgram($program)->create(['amount' => 2500]);

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonPath('data.0.attributes.purchaseOrderCount', 2)
            ->assertJsonPath('data.0.attributes.purchaseOrderTotal', 3500);
    });

    test('it returns zero aggregates for programs with no purchase orders', function () {
        $customer = Customer::factory()->create();
        Program::factory()->forCustomer($customer)->create();

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonPath('data.0.attributes.purchaseOrderCount', 0)
            ->assertJsonPath('data.0.attributes.purchaseOrderTotal', 0);
    });

    test('it excludes unprogrammed purchase orders from program aggregates', function () {
        $customer = Customer::factory()->create();
        $program = Program::factory()->forCustomer($customer)->create();

        PurchaseOrder::factory()->forCustomer($customer)->forProgram($program)->create(['amount' => 5000]);
        PurchaseOrder::factory()->forCustomer($customer)->create(['amount' => 9000, 'program_id' => null]);

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonPath('data.0.attributes.purchaseOrderCount', 1)
            ->assertJsonPath('data.0.attributes.purchaseOrderTotal', 5000)
            ->assertJsonPath('meta.unprogrammedPurchaseOrderCount', 1)
            ->assertJsonPath('meta.unprogrammedPurchaseOrderTotal', 9000);
    });

    test('it excludes purchase orders belonging to another customer from program aggregates', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $program = Program::factory()->forCustomer($customer)->create();

        PurchaseOrder::factory()->forCustomer($customer)->forProgram($program)->create(['amount' => 1000]);
        PurchaseOrder::factory()->forCustomer($otherCustomer)->forProgram($program)->create(['amount' => 9999]);

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonPath('data.0.attributes.purchaseOrderCount', 1)
            ->assertJsonPath('data.0.attributes.purchaseOrderTotal', 1000);
    });

    test('it returns accurate aggregates for customers with more than one hundred purchase orders', function () {
        $customer = Customer::factory()->create();
        $programA = Program::factory()->forCustomer($customer)->create(['name' => 'Program A']);
        $programB = Program::factory()->forCustomer($customer)->create(['name' => 'Program B']);
        $programC = Program::factory()->forCustomer($customer)->create(['name' => 'Program C']);

        PurchaseOrder::factory()->count(60)->forCustomer($customer)->forProgram($programA)->create(['amount' => 100]);
        PurchaseOrder::factory()->count(60)->forCustomer($customer)->forProgram($programB)->create(['amount' => 200]);
        PurchaseOrder::factory()->count(30)->forCustomer($customer)->forProgram($programC)->create(['amount' => 300]);
        PurchaseOrder::factory()->count(5)->forCustomer($customer)->create(['amount' => 50, 'program_id' => null]);

        $response = getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $programsByName = collect($response->json('data'))->keyBy('attributes.name');

        expect($programsByName['Program A']['attributes']['purchaseOrderCount'])->toBe(60)
            ->and($programsByName['Program A']['attributes']['purchaseOrderTotal'])->toBe(6000)
            ->and($programsByName['Program B']['attributes']['purchaseOrderCount'])->toBe(60)
            ->and($programsByName['Program B']['attributes']['purchaseOrderTotal'])->toBe(12000)
            ->and($programsByName['Program C']['attributes']['purchaseOrderCount'])->toBe(30)
            ->and($programsByName['Program C']['attributes']['purchaseOrderTotal'])->toBe(9000);

        $response
            ->assertJsonPath('meta.unprogrammedPurchaseOrderCount', 5)
            ->assertJsonPath('meta.unprogrammedPurchaseOrderTotal', 250);
    });

    test('it loads program aggregates without n plus one queries', function () {
        $customer = Customer::factory()->create();
        Program::factory()->count(3)->forCustomer($customer)->create();

        DB::enableQueryLog();

        getJson("/api/v1/customers/{$customer->id}/programs")->assertOk();

        expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(3);
    });
});
