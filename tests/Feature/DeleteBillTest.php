<?php

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class);

describe('guest user', function () {
    test('cannot delete a bill when not authenticated', function () {
        $transaction = Transaction::factory()->create();

        deleteJson("/api/v1/transactions/{$transaction->id}/bill")->assertUnauthorized();
    });
});

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    });

    test('can delete a draft bill', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        $bill = Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'draft',
        ]);

        deleteJson("/api/v1/transactions/{$transaction->id}/bill")
            ->assertNoContent();

        $this->assertDatabaseMissing('bills', [
            'id' => $bill->id,
        ]);
    });

    test('returns 422 when deleting non draft bill', function () {
        $transaction = Transaction::factory()->create(['user_id' => $this->user->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'issued',
            'issued_at' => now()->subDay(),
        ]);

        deleteJson("/api/v1/transactions/{$transaction->id}/bill")
            ->assertStatus(422);
    });

    test('returns 404 when deleting another users bill', function () {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $otherUser->id]);
        Bill::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => 'draft',
        ]);

        deleteJson("/api/v1/transactions/{$transaction->id}/bill")
            ->assertNotFound();
    });
});
