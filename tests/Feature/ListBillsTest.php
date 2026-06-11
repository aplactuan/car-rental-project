<?php

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot list bills when not authenticated', function () {
        getJson('/api/v1/bills')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('returns bills across all customers for the user', function () {
        $c1 = Customer::factory()->create();
        $c2 = Customer::factory()->create();
        $t1 = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $c1->id]);
        $t2 = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $c2->id]);
        Bill::factory()->create(['transaction_id' => $t1->id]);
        Bill::factory()->create(['transaction_id' => $t2->id]);

        getJson('/api/v1/bills')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    test('does not return bills for another users transactions', function () {
        $other = User::factory()->create();
        $customer = Customer::factory()->create();
        $mine = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $theirs = Transaction::factory()->create(['user_id' => $other->id, 'customer_id' => $customer->id]);
        Bill::factory()->create(['transaction_id' => $mine->id]);
        Bill::factory()->create(['transaction_id' => $theirs->id]);

        getJson('/api/v1/bills')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    test('supports the same list filters as customer bills', function () {
        $customer = Customer::factory()->create();
        $tIssued = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $tPaid = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        Bill::factory()->create(['transaction_id' => $tIssued->id, 'status' => 'issued']);
        $paid = Bill::factory()->create(['transaction_id' => $tPaid->id, 'status' => 'paid']);

        $query = http_build_query([
            'filter' => ['status' => 'paid'],
        ]);

        getJson("/api/v1/bills?{$query}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paid->id);
    });

    test('can search bills by invoice number', function () {
        $customer = Customer::factory()->create();
        $matchingTransaction = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $otherTransaction = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $matchingBill = Bill::factory()->create([
            'transaction_id' => $matchingTransaction->id,
            'invoice_number' => 'INV-260500123',
        ]);
        Bill::factory()->create([
            'transaction_id' => $otherTransaction->id,
            'invoice_number' => 'INV-260500999',
        ]);

        $query = http_build_query([
            'filter' => ['invoice_number' => '0123'],
        ]);

        getJson("/api/v1/bills?{$query}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingBill->id);
    });

    test('includes amount paid and remaining balance on each bill', function () {
        $customer = Customer::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $bill = Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'partially_paid',
            'amount' => 100_000,
        ]);
        $bill->payments()->create([
            'amount' => 35_000,
            'method' => 'cash',
            'reference_number' => 'REF-12345678',
            'paid_at' => now(),
        ]);

        getJson('/api/v1/bills')
            ->assertOk()
            ->assertJsonPath('data.0.attributes.amount', 100_000)
            ->assertJsonPath('data.0.attributes.amountPaid', 35_000)
            ->assertJsonPath('data.0.attributes.remainingBalance', 65_000);
    });

    test('includes transactions when include is set', function () {
        $customer = Customer::factory()->create();
        $tx = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'name' => 'Fleet week',
        ]);
        $bill = Bill::factory()->create(['transaction_id' => $tx->id]);

        getJson('/api/v1/bills?include=transaction')
            ->assertOk()
            ->assertJsonPath('data.0.id', $bill->id)
            ->assertJsonPath('included.0.type', 'transaction')
            ->assertJsonPath('included.0.attributes.name', 'Fleet week');
    });
});
