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
    test('cannot view billing summary when not authenticated', function () {
        getJson('/api/v1/billing/summary')->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('returns cash received total from paid bills and ar total from issued bills', function () {
        $customer = Customer::factory()->create();
        $tPaid = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
        ]);
        $tIssued = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
        ]);
        Bill::factory()->create([
            'transaction_id' => $tPaid->id,
            'status' => 'paid',
            'amount' => 500_000,
            'paid_at' => '2026-06-01 12:00:00',
        ]);
        Bill::factory()->create([
            'transaction_id' => $tIssued->id,
            'status' => 'issued',
            'amount' => 120_000,
            'issued_at' => '2026-05-01 12:00:00',
        ]);

        getJson('/api/v1/billing/summary')
            ->assertOk()
            ->assertJsonPath('data.type', 'billingSummary')
            ->assertJsonPath('data.attributes.cashReceivedTotal', 500_000)
            ->assertJsonPath('data.attributes.accountsReceivableTotal', 120_000);
    });

    test('scopes totals to a customer when filter customer id is set', function () {
        $c1 = Customer::factory()->create();
        $c2 = Customer::factory()->create();
        $t1 = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $c1->id]);
        $t2 = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $c2->id]);
        Bill::factory()->create([
            'transaction_id' => $t1->id,
            'status' => 'paid',
            'amount' => 100_000,
            'paid_at' => '2026-01-15 00:00:00',
        ]);
        Bill::factory()->create([
            'transaction_id' => $t2->id,
            'status' => 'paid',
            'amount' => 900_000,
            'paid_at' => '2026-01-15 00:00:00',
        ]);

        $query = http_build_query([
            'filter' => ['customer_id' => $c1->id],
        ]);

        getJson("/api/v1/billing/summary?{$query}")
            ->assertOk()
            ->assertJsonPath('data.attributes.cashReceivedTotal', 100_000)
            ->assertJsonPath('data.attributes.accountsReceivableTotal', 0);
    });

    test('filters cash received by paid at date range', function () {
        $customer = Customer::factory()->create();
        $tIn = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $tOut = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        Bill::factory()->create([
            'transaction_id' => $tIn->id,
            'status' => 'paid',
            'amount' => 50_000,
            'paid_at' => '2026-06-15 10:00:00',
        ]);
        Bill::factory()->create([
            'transaction_id' => $tOut->id,
            'status' => 'paid',
            'amount' => 999_000,
            'paid_at' => '2026-01-05 10:00:00',
        ]);

        $query = http_build_query([
            'filter' => [
                'paid_at' => [
                    'from' => '2026-06-01',
                    'to' => '2026-06-30',
                ],
            ],
        ]);

        getJson("/api/v1/billing/summary?{$query}")
            ->assertOk()
            ->assertJsonPath('data.attributes.cashReceivedTotal', 50_000);
    });

    test('limits accounts receivable to bills issued on or before as of', function () {
        $customer = Customer::factory()->create();
        $tOld = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $tNew = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        Bill::factory()->create([
            'transaction_id' => $tOld->id,
            'status' => 'issued',
            'amount' => 10_000,
            'issued_at' => '2026-03-01 00:00:00',
        ]);
        Bill::factory()->create([
            'transaction_id' => $tNew->id,
            'status' => 'issued',
            'amount' => 90_000,
            'issued_at' => '2026-08-01 00:00:00',
        ]);

        $query = http_build_query([
            'filter' => ['as_of' => '2026-06-01'],
        ]);

        getJson("/api/v1/billing/summary?{$query}")
            ->assertOk()
            ->assertJsonPath('data.attributes.accountsReceivableTotal', 10_000);
    });

    test('excludes another users bills from totals', function () {
        $other = User::factory()->create();
        $customer = Customer::factory()->create();
        $mine = Transaction::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);
        $theirs = Transaction::factory()->create(['user_id' => $other->id, 'customer_id' => $customer->id]);
        Bill::factory()->create([
            'transaction_id' => $mine->id,
            'status' => 'paid',
            'amount' => 1,
            'paid_at' => now(),
        ]);
        Bill::factory()->create([
            'transaction_id' => $theirs->id,
            'status' => 'paid',
            'amount' => 999_999,
            'paid_at' => now(),
        ]);

        getJson('/api/v1/billing/summary')
            ->assertOk()
            ->assertJsonPath('data.attributes.cashReceivedTotal', 1);
    });

    test('rejects unknown customer id for filter', function () {
        getJson('/api/v1/billing/summary?filter[customer_id]=00000000-0000-0000-0000-000000000000')
            ->assertUnprocessable();
    });

    test('rejects paid at to before from', function () {
        $query = http_build_query([
            'filter' => [
                'paid_at' => [
                    'from' => '2026-06-10',
                    'to' => '2026-06-01',
                ],
            ],
        ]);

        getJson("/api/v1/billing/summary?{$query}")
            ->assertUnprocessable();
    });
});
