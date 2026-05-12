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
    test('cannot list customer bills when not authenticated', function () {
        $customer = Customer::factory()->create();

        getJson("/api/v1/customers/{$customer->id}/bills")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        $this->customer = Customer::factory()->create();
    });

    test('returns paginated bills for own transactions on the customer', function () {
        $t1 = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $t2 = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $b1 = Bill::factory()->create(['transaction_id' => $t1->id, 'amount' => 100_000]);
        $b2 = Bill::factory()->create(['transaction_id' => $t2->id, 'amount' => 200_000]);

        $response = getJson("/api/v1/customers/{$this->customer->id}/bills");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'bill')
            ->assertJsonPath('data.1.type', 'bill');

        $ids = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        expect($ids)->toEqual(collect([$b1->id, $b2->id])->sort()->values()->all());
    });

    test('does not return bills from another users transactions for the same customer', function () {
        $other = User::factory()->create();
        $ownTx = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $otherTx = Transaction::factory()->create([
            'user_id' => $other->id,
            'customer_id' => $this->customer->id,
        ]);
        Bill::factory()->create(['transaction_id' => $ownTx->id]);
        Bill::factory()->create(['transaction_id' => $otherTx->id]);

        getJson("/api/v1/customers/{$this->customer->id}/bills")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    test('returns empty data when the customer has no bills for this user', function () {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/bills")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    test('filters by comma separated status', function () {
        $tIssued = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $tPaid = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Bill::factory()->create(['transaction_id' => $tIssued->id, 'status' => 'issued']);
        $paidBill = Bill::factory()->create(['transaction_id' => $tPaid->id, 'status' => 'paid']);

        $query = http_build_query([
            'filter' => [
                'status' => 'issued,paid',
            ],
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/bills?{$query}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $queryPaidOnly = http_build_query([
            'filter' => [
                'status' => 'paid',
            ],
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/bills?{$queryPaidOnly}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paidBill->id);
    });

    test('filters by issued at date range', function () {
        $tIn = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $tOut = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $inRange = Bill::factory()->create([
            'transaction_id' => $tIn->id,
            'issued_at' => '2026-06-15 12:00:00',
        ]);
        Bill::factory()->create([
            'transaction_id' => $tOut->id,
            'issued_at' => '2026-01-10 12:00:00',
        ]);

        $query = http_build_query([
            'filter' => [
                'issued_at' => [
                    'from' => '2026-06-01',
                    'to' => '2026-06-30',
                ],
            ],
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/bills?{$query}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inRange->id);
    });

    test('sorts by negative issued at with most recent first', function () {
        $tOld = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $tNew = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Bill::factory()->create([
            'transaction_id' => $tOld->id,
            'issued_at' => '2026-02-01 00:00:00',
        ]);
        $newer = Bill::factory()->create([
            'transaction_id' => $tNew->id,
            'issued_at' => '2026-05-01 00:00:00',
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/bills?sort=-issued_at")
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id);
    });

    test('includes transaction payloads when include is transaction', function () {
        $tx = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'name' => 'Rental June',
        ]);
        $bill = Bill::factory()->create(['transaction_id' => $tx->id]);

        getJson("/api/v1/customers/{$this->customer->id}/bills?include=transaction")
            ->assertOk()
            ->assertJsonPath('data.0.id', $bill->id)
            ->assertJsonPath('included.0.type', 'transaction')
            ->assertJsonPath('included.0.id', $tx->id)
            ->assertJsonPath('included.0.attributes.name', 'Rental June');
    });

    test('rejects invalid include parameter', function () {
        getJson("/api/v1/customers/{$this->customer->id}/bills?include=booking")
            ->assertUnprocessable();
    });

    test('rejects invalid status in filter', function () {
        getJson("/api/v1/customers/{$this->customer->id}/bills?filter[status]=void")
            ->assertUnprocessable();
    });

    test('rejects issued at to before from', function () {
        $query = http_build_query([
            'filter' => [
                'issued_at' => [
                    'from' => '2026-06-10',
                    'to' => '2026-06-01',
                ],
            ],
        ]);

        getJson("/api/v1/customers/{$this->customer->id}/bills?{$query}")
            ->assertUnprocessable();
    });
});
