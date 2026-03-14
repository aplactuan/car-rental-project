<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot delete a customer when not authenticated', function () {
        $customer = Customer::factory()->create();

        deleteJson("/api/v1/customers/{$customer->id}")
            ->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can delete a customer', function () {
        $customer = Customer::factory()->create();

        deleteJson("/api/v1/customers/{$customer->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    });
});
