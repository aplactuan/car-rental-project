<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot access customer transaction endpoints when not authenticated', function () {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create(['customer_id' => $customer->id]);

        postJson("/api/v1/customers/{$customer->id}/transactions")->assertUnauthorized();
        getJson("/api/v1/customers/{$customer->id}/transactions")->assertUnauthorized();
        getJson("/api/v1/customers/{$customer->id}/transactions/{$transaction->id}")->assertUnauthorized();
        putJson("/api/v1/customers/{$customer->id}/transactions/{$transaction->id}", [
            'customer_id' => $customer->id,
            'name' => 'Lease agreement',
        ])->assertUnauthorized();
        deleteJson("/api/v1/customers/{$customer->id}/transactions/{$transaction->id}")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can manage customer scoped transactions', function () {
        $customer = Customer::factory()->create();
        $newCustomer = Customer::factory()->create();

        $createResponse = postJson("/api/v1/customers/{$customer->id}/transactions", [
            'name' => 'Summer rental',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.type', 'transaction')
            ->assertJsonPath('data.attributes.userId', $this->user->id)
            ->assertJsonPath('data.attributes.customerId', $customer->id)
            ->assertJsonPath('data.attributes.name', 'Summer rental');

        $transactionId = $createResponse->json('data.id');

        getJson("/api/v1/customers/{$customer->id}/transactions")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $transactionId);

        getJson("/api/v1/customers/{$customer->id}/transactions/{$transactionId}")
            ->assertOk()
            ->assertJsonPath('data.id', $transactionId)
            ->assertJsonPath('data.attributes.customerId', $customer->id);

        putJson("/api/v1/customers/{$customer->id}/transactions/{$transactionId}", [
            'customer_id' => $newCustomer->id,
            'name' => 'Updated label',
        ])->assertOk()
            ->assertJsonPath('data.id', $transactionId)
            ->assertJsonPath('data.attributes.customerId', $newCustomer->id)
            ->assertJsonPath('data.attributes.name', 'Updated label');

        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'user_id' => $this->user->id,
            'customer_id' => $newCustomer->id,
            'name' => 'Updated label',
        ]);

        getJson("/api/v1/customers/{$customer->id}/transactions/{$transactionId}")
            ->assertNotFound();

        deleteJson("/api/v1/customers/{$newCustomer->id}/transactions/{$transactionId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('transactions', [
            'id' => $transactionId,
        ]);
    });

    test('returns not found for another users customer transaction', function () {
        $customer = Customer::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'customer_id' => $customer->id,
        ]);

        getJson("/api/v1/customers/{$customer->id}/transactions/{$transaction->id}")
            ->assertNotFound();
    });
});
