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
