<?php

use App\Models\Customer;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertJsonPath('data.0.relationships.customer.data.id', $customer->id)
            ->assertJsonMissingPath('meta')
            ->assertJsonMissingPath('links');
    });

    test('it returns all programs for the customer without pagination', function () {
        $customer = Customer::factory()->create();
        Program::factory()->count(5)->forCustomer($customer)->create();

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonMissingPath('meta')
            ->assertJsonMissingPath('links');
    });

    test('it returns an empty list when the customer has no programs', function () {
        $customer = Customer::factory()->create();

        getJson("/api/v1/customers/{$customer->id}/programs")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    test('it returns not found for a missing customer', function () {
        getJson('/api/v1/customers/'.fake()->uuid().'/programs')
            ->assertNotFound();
    });
});
