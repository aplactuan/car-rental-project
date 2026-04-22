<?php

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot view a bill when not authenticated', function () {
        $transaction = Transaction::factory()->create();

        getJson("/api/v1/transactions/{$transaction->id}/bill")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can view own bill', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'amount' => 920000,
            'status' => 'draft',
        ]);

        getJson("/api/v1/transactions/{$transaction->id}/bill")
            ->assertOk()
            ->assertJsonPath('data.type', 'bill')
            ->assertJsonPath('data.attributes.amount', 920000)
            ->assertJsonPath('data.relationships.transaction.data.id', $transaction->id);
    });

    test('returns 404 when transaction has no bill', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bill")
            ->assertNotFound();
    });

    test('returns 404 when viewing another users bill', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);

        getJson("/api/v1/transactions/{$transaction->id}/bill")
            ->assertNotFound();
    });
});
