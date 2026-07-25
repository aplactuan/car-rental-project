<?php

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can create a transaction with a po number', function () {
        $customer = Customer::factory()->create();

        $response = postJson('/api/v1/transactions', [
            'customer_id' => $customer->id,
            'name' => 'PO-backed rental',
            'po_number' => 'PO-1001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.poNumber', 'PO-1001');

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'po_number' => 'PO-1001',
        ]);
    });

    test('can create a transaction without a po number', function () {
        $customer = Customer::factory()->create();

        postJson('/api/v1/transactions', [
            'customer_id' => $customer->id,
            'name' => 'No PO',
        ])->assertCreated()
            ->assertJsonPath('data.attributes.poNumber', null);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'name' => 'No PO',
            'po_number' => null,
        ]);
    });

    test('rejects duplicate po numbers on create', function () {
        $customer = Customer::factory()->create();
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'po_number' => 'PO-1001',
        ]);

        postJson('/api/v1/transactions', [
            'customer_id' => $customer->id,
            'name' => 'Duplicate PO',
            'po_number' => 'PO-1001',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/po_number');
    });

    test('can update a transaction po number', function () {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'po_number' => 'PO-1001',
        ]);

        putJson("/api/v1/customers/{$customer->id}/transactions/{$transaction->id}", [
            'customer_id' => $customer->id,
            'name' => $transaction->name,
            'po_number' => 'PO-2002',
        ])->assertOk()
            ->assertJsonPath('data.attributes.poNumber', 'PO-2002');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'po_number' => 'PO-2002',
        ]);
    });

    test('allows keeping the same po number on update', function () {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'po_number' => 'PO-1001',
        ]);

        putJson("/api/v1/customers/{$customer->id}/transactions/{$transaction->id}", [
            'customer_id' => $customer->id,
            'name' => 'Renamed',
            'po_number' => 'PO-1001',
        ])->assertOk()
            ->assertJsonPath('data.attributes.poNumber', 'PO-1001');
    });

    test('rejects updating to another transactions po number', function () {
        $customer = Customer::factory()->create();
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'po_number' => 'PO-TAKEN',
        ]);
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'po_number' => 'PO-OWN',
        ]);

        putJson("/api/v1/customers/{$customer->id}/transactions/{$transaction->id}", [
            'customer_id' => $customer->id,
            'name' => $transaction->name,
            'po_number' => 'PO-TAKEN',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/po_number');
    });

    test('can filter transactions by po number', function () {
        $match = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'po_number' => 'PO-FIND-ME',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'po_number' => 'PO-OTHER',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'po_number' => null,
        ]);

        getJson('/api/v1/transactions?po_number=PO-FIND-ME')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id)
            ->assertJsonPath('data.0.attributes.poNumber', 'PO-FIND-ME');
    });

    test('can filter customer transactions by po number', function () {
        $customer = Customer::factory()->create();
        $match = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'po_number' => 'PO-CUST-1',
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'po_number' => 'PO-CUST-2',
        ]);

        getJson("/api/v1/customers/{$customer->id}/transactions?po_number=PO-CUST-1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    });
});
