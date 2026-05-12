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
    test('cannot filter transactions when not authenticated', function () {
        getJson('/api/v1/transactions?has_bill=false')->assertUnauthorized();
    });
});

describe('authenticated user — global transactions', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('returns only unbilled transactions when has_bill is false', function () {
        $unbilled = Transaction::factory()->create(['user_id' => $this->user->id]);
        $billed = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create(['transaction_id' => $billed->id]);

        getJson('/api/v1/transactions?has_bill=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unbilled->id);
    });

    test('returns only billed transactions when has_bill is true', function () {
        $unbilled = Transaction::factory()->create(['user_id' => $this->user->id]);
        $billed = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create(['transaction_id' => $billed->id]);

        getJson('/api/v1/transactions?has_bill=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $billed->id);
    });

    test('returns all transactions when has_bill filter is absent', function () {
        $unbilled = Transaction::factory()->create(['user_id' => $this->user->id]);
        $billed = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create(['transaction_id' => $billed->id]);

        getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    test('does not include another users transactions in has_bill filter results', function () {
        $otherUser = User::factory()->create();
        $ownUnbilled = Transaction::factory()->create(['user_id' => $this->user->id]);
        $otherUnbilled = Transaction::factory()->create(['user_id' => $otherUser->id]);

        getJson('/api/v1/transactions?has_bill=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownUnbilled->id);
    });

    test('rejects invalid has_bill value', function () {
        getJson('/api/v1/transactions?has_bill=notabool')
            ->assertUnprocessable();
    });
});

describe('authenticated user — customer-scoped transactions', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        $this->customer = Customer::factory()->create();
    });

    test('returns only unbilled transactions for the customer when has_bill is false', function () {
        $unbilled = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $billed = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Bill::factory()->create(['transaction_id' => $billed->id]);

        getJson("/api/v1/customers/{$this->customer->id}/transactions?has_bill=false")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unbilled->id);
    });

    test('returns only billed transactions for the customer when has_bill is true', function () {
        $unbilled = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $billed = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Bill::factory()->create(['transaction_id' => $billed->id]);

        getJson("/api/v1/customers/{$this->customer->id}/transactions?has_bill=true")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $billed->id);
    });

    test('returns all customer transactions when has_bill filter is absent', function () {
        Transaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/transactions")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    test('does not include other customers transactions in the filter results', function () {
        $otherCustomer = Customer::factory()->create();

        $ownUnbilled = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $otherCustomer->id,
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/transactions?has_bill=false")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownUnbilled->id);
    });
});
